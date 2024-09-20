<?php

use Transpher\Nostr\NIP44;

function vectors(string $name): object {
    return json_decode(file_get_contents(__DIR__ . '/' . $name . '.json'), false);
}

function openKey(string $key): \Elliptic\EC\KeyPair {
    $ec = new \Elliptic\EC('secp256k1');
    return $ec->keyFromPrivate($key);
}

describe('NIP-44 v2', function () {
    describe('valid', function () {
        it('is sane', function () {
            $key1 = openKey('00f4b7ff7cccc98813a69fae3df222bfe3f4e28f764bf91b4a10d8096ce446b254');
            expect('00' . $key1->getPrivate('hex'))->toBe('00f4b7ff7cccc98813a69fae3df222bfe3f4e28f764bf91b4a10d8096ce446b254');

            $key = hex2bin(
                    '000102030405060708090a0b0c0d0e0f' .
                    '101112131415161718191a1b1c1d1e1f' .
                    '202122232425262728292a2b2c2d2e2f' .
                    '303132333435363738393a3b3c3d3e3f' .
                    '404142434445464748494a4b4c4d4e4f'
            );
            $salt = hex2bin(
                    '606162636465666768696a6b6c6d6e6f' .
                    '707172737475767778797a7b7c7d7e7f' .
                    '808182838485868788898a8b8c8d8e8f' .
                    '909192939495969798999a9b9c9d9e9f' .
                    'a0a1a2a3a4a5a6a7a8a9aaabacadaeaf'
            );
            expect(bin2hex(NIP44::hkdf_extract($key, $salt)))->toBe('06a6b88c5853361a06104c9ceb35b45cef760014904671014a193f40c15fc244');
        });

        it('hkdf works', function () {
            foreach (vectors('hkdf')->sha256 as $vector) {
                $vector_salt = $vector->salt ? hex2bin($vector->salt) : '';
                $vector_info = $vector->info ? hex2bin($vector->info) : '';
                $PRK = NIP44::hkdf_extract(hex2bin($vector->IKM), $vector_salt);
                expect(bin2hex($PRK))->toBe($vector->PRK);
                $OKM = NIP44::hkdf(hex2bin($vector->IKM), $vector_salt, $vector_info, $vector->L);
                expect(bin2hex($OKM))->toBe($vector->OKM);
                expect(bin2hex(NIP44::hkdf(hex2bin($vector->IKM), $vector_salt, $vector_info, $vector->L, ["cleanup" => true])))->toBe($vector->OKM);
                expect(bin2hex(NIP44::hkdf(hex2bin($vector->IKM), $vector_salt, $vector_info, $vector->L, ["cleanup" => false])))->toBe($vector->OKM);
            }
        });

        it('shared_secret', function () {
            // https://github.com/paulmillr/noble-secp256k1/blob/main/test/wycheproof/ecdh_secp256k1_test.json
            foreach (vectors('ecdh-secp256k1')->testGroups[0]->tests as $vector) {
                if ($vector->result === 'valid') {
                    $secret = NIP44::getSharedSecret($vector->private, substr($vector->public, 46));
                    expect(str_pad($secret, 64, '0', STR_PAD_LEFT))->toBe($vector->shared);
                }
            }
        });
        it('chacha20', function () {
            // https://github.com/paulmillr/noble-secp256k1/blob/main/test/wycheproof/ecdh_secp256k1_test.json
            foreach (vectors('chacha20-poly1305')->testGroups[0]->tests as $vector) {
                if ($vector->result === 'valid') {
                    $encrypted = NIP44::chacha20_poly1305(hex2bin($vector->key), hex2bin($vector->iv), hex2bin($vector->aad), hex2bin($vector->msg));
                    expect(bin2hex($encrypted))->toBe($vector->ct . $vector->tag);
                }
            }
        });
        it('get_conversation_key', function () {
            //https://github.com/paulmillr/nip44/blob/main/javascript/test/nip44.vectors.json
            foreach (vectors('nip44')->v2->valid->get_conversation_key as $vector) {
                expect(bin2hex(NIP44::getConversationKey($vector->sec1, '02' . $vector->pub2)))->toBe($vector->conversation_key);
            }
        });
        it('gets message keys', function () {
            $conversation_key = hex2bin(vectors('nip44')->v2->valid->get_message_keys->conversation_key);
            expect($conversation_key)->not()->toBeEmpty();
            foreach (vectors('nip44')->v2->valid->get_message_keys->keys as $vector) {
                list($chacha_key, $chacha_nonce, $hmac_key) = NIP44::getMessageKeys($conversation_key, hex2bin($vector->nonce));
                expect(bin2hex($chacha_key))->toBe($vector->chacha_key);
                expect(bin2hex($chacha_nonce))->toBe($vector->chacha_nonce);
                expect(bin2hex($hmac_key))->toBe($vector->hmac_key);
            }
        });

        it('calcs padded length correctly', function () {
            foreach (vectors('nip44')->v2->valid->calc_padded_len as $vector) {
                expect(NIP44::calcPaddedLength($vector[0]))->toBe($vector[1]);
            }
        });

        it('can encrypt & decrypt', function () {
            foreach (vectors('nip44')->v2->valid->encrypt_decrypt as $vector) {
                $key = openKey($vector->sec2);
                expect($key->validate()['result'])->toBeTrue();

                $pub2 = $key->getPublic('hex');

                $conversation_key = NIP44::getConversationKey($vector->sec1, $pub2);
                expect(bin2hex($conversation_key))->toBe($vector->conversation_key);

                expect(NIP44::decrypt($vector->payload, $conversation_key))->toBe($vector->plaintext, 'Unable to properly decrypt vector payload');

                $payload = NIP44::encrypt($vector->plaintext, $conversation_key, hex2bin($vector->nonce));
                expect(NIP44::decrypt($payload, $conversation_key))->toBe($vector->plaintext, 'Unable to properly decrypt self encrypted payload');

                expect($payload)->toBe($vector->payload, 'Unable to properly encrypt vector message');
            }
        });
    });
});

