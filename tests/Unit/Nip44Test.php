<?php

use Transpher\Nostr\NIP44;

function vectors(string $name) : object {
    return json_decode(file_get_contents(__DIR__ . '/'.$name.'.json'), false);
}

describe('NIP-44 v2', function() {
    describe('valid', function() {
        it('is sane', function() {
            $ec = new \Elliptic\EC('secp256k1');
            $key1 = $ec->keyFromPrivate('00f4b7ff7cccc98813a69fae3df222bfe3f4e28f764bf91b4a10d8096ce446b254');
            expect('00' . $key1->getPrivate('hex'))->toBe('00f4b7ff7cccc98813a69fae3df222bfe3f4e28f764bf91b4a10d8096ce446b254');
            
            $key = hex2bin(
                '000102030405060708090a0b0c0d0e0f' .
                  '101112131415161718191a1b1c1d1e1f' .
                  '202122232425262728292a2b2c2d2e2f' .
                  '303132333435363738393a3b3c3d3e3f' .
                  '404142434445464748494a4b4c4d4e4f'
              );
            $info = hex2bin(
              'b0b1b2b3b4b5b6b7b8b9babbbcbdbebf' .
                'c0c1c2c3c4c5c6c7c8c9cacbcccdcecf' .
                'd0d1d2d3d4d5d6d7d8d9dadbdcdddedf' .
                'e0e1e2e3e4e5e6e7e8e9eaebecedeeef' .
                'f0f1f2f3f4f5f6f7f8f9fafbfcfdfeff'
            );
            $salt = hex2bin(
              '606162636465666768696a6b6c6d6e6f' .
                '707172737475767778797a7b7c7d7e7f' .
                '808182838485868788898a8b8c8d8e8f' .
                '909192939495969798999a9b9c9d9e9f' .
                'a0a1a2a3a4a5a6a7a8a9aaabacadaeaf'
            );
            expect(NIP44::hkdf_extract($key, $salt))->toBe('06a6b88c5853361a06104c9ceb35b45cef760014904671014a193f40c15fc244');
        });
        
        it('shared_secret', function() {
            // https://github.com/paulmillr/noble-secp256k1/blob/main/test/wycheproof/ecdh_secp256k1_test.json
            foreach (vectors('ecdh-secp256k1')->testGroups[0]->tests as $vector) {
                if ($vector->result === 'valid') {
                    $secret = NIP44::getSharedSecret($vector->private, substr($vector->public, 46));
                    expect(str_pad($secret, 64, '0', STR_PAD_LEFT))->toBe($vector->shared);
                }
            }
        });
        it('get_conversation_key', function() {
            //https://github.com/paulmillr/nip44/blob/main/javascript/test/nip44.vectors.json
            foreach (vectors('nip44')->v2->valid->get_conversation_key as $v) {
                expect(NIP44::getConversationKey($v->sec1, $v->pub2))->toBe($v->conversation_key);
            }
        });
    });
});

