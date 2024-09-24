<?php

use Transpher\Nostr\NIP44;

function vectors(string $name): object {
    return json_decode(file_get_contents(__DIR__ . '/vectors/' . $name . '.json'), false);
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
            expect(bin2hex(NIP44::hmac_digest($salt, $key)))->toBe('06a6b88c5853361a06104c9ceb35b45cef760014904671014a193f40c15fc244');
        });

        it('hkdf works', function () {
            foreach (vectors('hkdf')->sha256 as $vector) {
                $vector_salt = $vector->salt ? hex2bin($vector->salt) : '';
                $vector_info = $vector->info ? hex2bin($vector->info) : '';
                $PRK = NIP44::hmac_digest($vector_salt, hex2bin($vector->IKM));
                expect(bin2hex($PRK))->toBe($vector->PRK);
                $OKM = NIP44::hkdf(hex2bin($vector->IKM), $vector_salt, $vector_info, $vector->L);
                expect(bin2hex($OKM))->toBe($vector->OKM);
                expect(bin2hex(NIP44::hkdf(hex2bin($vector->IKM), $vector_salt, $vector_info, $vector->L, ["cleanup" => true])))->toBe($vector->OKM);
                expect(bin2hex(NIP44::hkdf(hex2bin($vector->IKM), $vector_salt, $vector_info, $vector->L, ["cleanup" => false])))->toBe($vector->OKM);
            }
        });

        it('get_conversation_key', function () {
            //https://github.com/paulmillr/nip44/blob/main/javascript/test/nip44.vectors.json
            foreach (vectors('nip44')->v2->valid->get_conversation_key as $vector) {
                $key = NIP44::getConversationKey(hex2bin($vector->sec1), hex2bin('02' . $vector->pub2));
                expect($key)->not()->toBeFalse();
                expect(bin2hex($key))->toBe($vector->conversation_key, $vector->note??'');
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

                $conversation_key = NIP44::getConversationKey(hex2bin($vector->sec1), hex2bin($pub2));
                expect($conversation_key)->not()->toBeFalse();
                expect(bin2hex($conversation_key))->toBe($vector->conversation_key);

                expect(NIP44::decrypt($vector->payload, $conversation_key))->toBe($vector->plaintext, 'Unable to properly decrypt vector payload');

                $payload = NIP44::encrypt($vector->plaintext, $conversation_key, hex2bin($vector->nonce));
                expect(NIP44::decrypt($payload, $conversation_key))->toBe($vector->plaintext, 'Unable to properly decrypt self encrypted payload');

                expect($payload)->toBe($vector->payload, 'Unable to properly encrypt vector message');
            }
        });
        
        
        it('can encrypt & decrypt long messages', function () {
            foreach (vectors('nip44')->v2->valid->encrypt_decrypt_long_msg as $vector) {
                $plaintext = str_repeat($vector->pattern, $vector->repeat);
                expect(hash('sha256', $plaintext))->toBe($vector->plaintext_sha256);
                
                $payload = NIP44::encrypt($plaintext, hex2bin($vector->conversation_key), hex2bin($vector->nonce));
                expect(hash('sha256', $payload))->toBe($vector->payload_sha256, 'Unable to properly encrypt long text');
                
                expect(NIP44::decrypt($payload, hex2bin($vector->conversation_key)))->toBe($plaintext, 'Unable to properly decrypt self encrypted payload');
            }
        });
    });
    
    
  describe('invalid', function() {
    it('encrypt_msg_lengths', function() {
      foreach (vectors('nip44')->v2->invalid->encrypt_msg_lengths as $vector) {
          expect(NIP44::encrypt(str_repeat('a', $vector), random_bytes(32), ''))->toBeFalse();
      }
    });
    it('decrypt', function() {
      foreach (vectors('nip44')->v2->invalid->decrypt as $vector) {
        expect(NIP44::decrypt($vector->payload, hex2bin($vector->conversation_key)))->toBeFalse($vector->note);
      }
    });
    it('get_conversation_key', function() {
      foreach (vectors('nip44')->v2->invalid->get_conversation_key as $vector) {
        expect(NIP44::getConversationKey(hex2bin($vector->sec1), hex2bin($vector->pub2)))->toBeFalse($vector->note);
      }
    });
  });
});

