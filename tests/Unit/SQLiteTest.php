<?php

it('creates a table `event` if not exists', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\Stores\SQLite($sqlite, Mockery::spy(\Psr\Log\LoggerInterface::class));
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    $expected_tables = ['event', 'tag', 'tag_value'];
    foreach ($expected_tables as $expected_table) {
        $table = $sqlite->querySingle("SELECT name FROM sqlite_schema WHERE type='table' AND name='{$expected_table}'");
        expect($table)->toBe($expected_table);
    }
});

//// this requires a copy of transpher.sqlite, which I am not going to version for obious reasons
//it('real life test', function () {
//    $db_file = ROOT_DIR . '/data/transpher.sqlite';
//    $sqlite = new SQLite3($db_file);
//    expect($db_file)->toBeFile();
//
//    $store = new nostriphant\Transpher\Stores\SQLite($sqlite, Mockery::spy(\Psr\Log\LoggerInterface::class));
//
//    $filter_prototypes = json_decode('[{"kinds":[34235,30023,5,9802,10002,0,6,1,30311],"authors":["04c915daefee38317fa734444acee390a8269fe5810b2241e5e6dd343dfbecc9","83e818dfbeccea56b0f551576b3fd39a7a50e1d8159343500368fa085ccd964b","82341f882b6eabcd2ba7f1ef90aad961cf074af15b9ef44a09f9d2a8fbfbe6a2","33bd77e5394520747faae1394a4af5fa47f404389676375b6dc7be865ed81452","55f04590674f3648f4cdc9dc8ce32da2a282074cd0b020596ee033d12d385185","aef0d6b212827f3ba1de6189613e6d4824f181f567b1205273c16895fdaf0b23","85080d3bad70ccdcd7f74c29a44f55bb85cbcd3dd0cbb957da1d215bdb931204","123afae7d187ba36d6ddcd97dbf4acc59aeffe243f782592ff8f25ed579df306","35d26e4690cbe1a898af61cc3515661eb5fa763b57bd0b42e45099c8b32fd50f","9267545d2917b80f707ffdb44a8ff979182568ef7baa04ee756b1f01d4e3688a","59ffbe1fc829decf90655438bd2df3a7b746ef4a04634d4ee9e280bb6ce5f14e","84dee6e676e5bb67b4ad4e042cf70cbd8681155db535942fcc6a0533858a7240","ffbcb7069f7aa5d6db129eb39e7d8a9789466d255a637bac1ebf8617b0574044","43baaf0c28e6cfb195b17ee083e19eb3a4afdfac54d9b6baf170270ed193e34c","0fe0b18b4dbf0e0aa40fcd47209b2a49b3431fc453b460efcf45ca0bd16bd6ac","3f770d65d3a764a9c5cb503ae123e62ec7598ad035d836e2a810f3877a745b24","da1a336379dd61d16d90468031efca9520dbd3dfc31f66c172d2a4ec7aab2c74","32ee4f9325675439591fb6962a4b883d23b15212610a0b78593341846f6dd370","031cdf9461f7688b8ccca79d3dfe99ba14ebcafe79d8486add306c8e3c51ee3f","32e1827635450ebb3c5a7d12c1f8e7b2b514439ac10a67eef3d9fd9c5c68e245","b8a9df8218084e490d888342a9d488b7cf0fb20b1a19b963becd68ed6ab5cbbd","bbb5dda0e15567979f0543407bdc2033d6f0bbb30f72512a981cfdb2f09e2747","e87c737b6c73d9c2c3c4fad3fdd801b98e359ef0b150d43ed5531d3dcc2c0e54","8b12bddc423189c660156eab1ea04e1d44cc6621c550c313686705f704dda895","5ea4648045bb1ff222655ddd36e6dceddc43590c26090c486bef38ef450da5bd","5eca50a04afaefe55659fb74810b42654e2268c1acca6e53801b9862db74a83a","c8383d81dd24406745b68409be40d6721c301029464067fcc50a25ddf9139549","0497384b57b43c107a778870462901bf68e0e8583b32e2816563543c059784a4","5e5359da0518d38658df72e05cf2a2ff2e983d5498c71fb6db3c21c033dbaead","aa9047325603dacd4f8142093567973566de3b1e20a89557b728c3be4c6a844b","fd6c05b160f664295a55abf3a70b9e181cf87f29c3ede21cfab88d86238baf7f","e771af0b05c8e95fcdf6feb3500544d2fb1ccd384788e9f490bb3ee28e8ed66f","c4eabae1be3cf657bc1855ee05e69de9f059cb7a059227168b80b89761cbc4e0","08eade50df51da4a42f5dc045e35b371902e06d6a805215bec3d72dc687ccb04","1af54955936be804f95010647ea5ada5c7627eddf0734a7f813bba0e31eed960","a341f45ff9758f570a21b000c17d4e53a3a497c8397f26c0e6d61e5acffc7a98","9020fe7857bd2392d504beeb9e568776f507784fb5b5a94af7b5ef1ae9780289","205ed9240e40928b44e0afdabbb5b45d779169a140725fb4294d6064748ed33c","f07e0b1af066b4838386360a1a2cbb374429a9fbaab593027f3fcd3bd3b5c367","ad5f2f2e23271a4227dd67bc564d9e9b2e9918090dd6b8c2dea902b34433f9e0","06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71","53a91e3a64d1f658e983ac1e4f9e0c697f8f33e01d8debe439f4c1a92113f592","97c70a44366a6535c145b333f973ea86dfdc2d7a99da618c40c64705ad98e322","339d7804b6a69b7ef05a169d72ca3e977f64eb00ab6eedf21af0a2c2327691b3","23b1a71c129ef53fdcf85f81dc20a017cf1ef421b7e5649c84bcdddf673bed43","4b50eed23020d8cdccad8ed61cef0f4b631e728bb8e4008d12142f3300f52785","020f2d21ae09bf35fcdfb65decf1478b846f5f728ab30c5eaabcd6d081a81c3e","0396d971ffabe7f6f4367e357953e94348ce4e9da6888a66b070a1877eec798f","9358c67695d9e78bde2bf3ce1eb0a5059553687632a177e7d25deeff9f2912fc","27f186204dff66a612f584cc5e1ed20d8f43bd7b56706db19e56c59dc4d962ca","714f9dc34325c2cf099bbb834833540bc2e1cd82757fcc4df8bfbda076659adb","0dc2dcb14d89f94b8a1590e178e9fbcb2ef1cb0be175a283842f9dc54787801a","129f51895d12ab3c9cdc26cb22bdbb9de918e5f1cff98943f3860ef53a441803","1afe0c74e3d7784eba93a5e3fa554a6eeb01928d12739ae8ba4832786808e36d","99bb5591c9116600f845107d31f9b59e2f7c7e09a1ff802e84f1d43da557ca64","4fda8b10d2d955b1f85f1e65bbb624dfc46575d70982e86462fd18a77bbee962","faabf30511304604100eef697402a2c70491a156ea05d018157f7324c4e6c479","8e740e56cd9408480d3fa7674a1c7d7300878cca373ab2e3be7ea01c341215f8","0e8c41eb946657188ea6f2aac36c25e393fff4d4149a83679220d66595ff0faa","3356de61b39647931ce8b2140b2bab837e0810c0ef515bbe92de0248040b8bdd","1739d937dc8c0c7370aa27585938c119e25c41f6c441a5d34c6d38503e3136ef","04f7dda09c0e8f1117379d5f21b50324af3383e19adc20407638cef9c4d00b46","472f440f29ef996e92a186b8d320ff180c855903882e59d50de1b8bd5669301e","b7cf9f42a796b091e843dce919d3ef4c0dc82e029452edf0bdbcdeb9ecb93e78","18582f36fc4fa74bcfaa64fb26b30515097d408a0974defbaa2a76d10da22162","59fbee7369df7713dbbfa9bbdb0892c62eba929232615c6ff2787da384cb770f","aa55a479ad6934d0fd78f3dbd88515cd1ca0d7a110812e711380d59df7598935","787338757fc25d65cd929394d5e7713cf43638e8d259e8dcf5c73b834eb851f2","c1fc7771f5fa418fd3ac49221a18f19b42ccb7a663da8f04cbbf6c08c80d20b1","69a80567e79b6b9bc7282ad595512df0b804784616bedb623c122fad420a2635","caced545f980fb26be3cba0061b55b79b7c04c717ce0bdf1ddde07a9f4ffc2c7","765609c7ece4a9a5262ace318801e4798394a5908723ec7d4b48f841b488a3e9","7ef1d9f80efcbe8c879e38bde4a24016fca93c7874a22a6e4a8b5062bfed835e","ab8cb80e5e42a5c45fcf0a6c297e758b113a87daa5028b10b22b8adf5395d502","8e2fa1fff31f0941281ba78f1d11d411aa45cb9597e9a2c890d4aeb0953c1f03","0aa39e5aef99a000a7bdb0b499158c92bc4aa20fb65931a52d055b5eb6dff738","c51747fe7c448861e876d2e927c5b26d555035209fadbd1cb8f042b7ce07b667","c3e6982c7f93e443d99f2d22c3d6fc6ba61475af11bcf289f927a7b905fffe51","3e294d2fd339bb16a5403a86e3664947dd408c4d87a0066524f8a573ae53ca8e","e88a691e98d9987c964521dff60025f60700378a4879180dcbbb4a5027850411","78ce6faa72264387284e647ba6938995735ec8c7d5c5a65737e55130f026307d","72d0d2e308f6baef854517e37aeb5f395c3f5e1c5be71e636b19697aad459635","d26788e6fd137d435f4b18e9a91a56752a00392cba7721d966657345a11aeeab","f48cd1431fdb76ae9603c4fd6ad30f96643062d4d7a73a92cdce98d03dd15d13","89e14be49ed0073da83b678279cd29ba5ad86cf000b6a3d1a4c3dc4aa4fdd02c","fa90c095c6eca1fd3813beb3cfb054836cfa217d949d29a893ad20523123e2ac","ece3317bf8163930b5dafae50596b740b0608433b78568886a9a712a91a5d59b","7e66a18e115940c410d297b07b70f8d902cd8d8310977091b5108017012db7fc","9ce71f1506ccf4b99f234af49bd6202be883a80f95a155c6e9a1c36fd7e780c7","50d94fc2d8580c682b071a542f8b1e31a200b0508bab95a33bef0855df281d63","b3ac53e4eb5062b7f3747e63fe73e671174daf06660ef71dc72a71c971edd893","8047df981a97dd41b48f554ac00e90bd62348fe65384c88ef29032d752857143","460c25e682fda7832b52d1f22d3d22b3176d972f60dcdc3212ed8c92ef85065c","218238431393959d6c8617a3bd899303a96609b44a644e973891038a7de8622d","ab7ca98644f44cf2abfa2932553a27345a7f6cbca7f16cbbddcc7c6511f6acec","be1d89794bf92de5dd64c1e60f6a2c70c140abac9932418fee30c5c637fe9479","7c2aa656b1054baaccbea9a5833b8181fa40f7b8f6a0ebf8001643ad4e8063ac","0ff244cca0eaab9e699693e44b3b18ebbdf674ee27d21d52a9702b57bb0a6d2b","dc57c8ff44a150a5efb8b0151651889d0bcf5f9ad20c839bb245c542ac1bd1a6","d3b2757dc5910d38cc02ea6d51419eb2de74fb109716501d7aa7732635b9f11b","47750177bb6bb113784e4973f6b2e3dd27ef1eff227d6e38d0046d618969e41a","e1055729d51e037b3c14e8c56e2c79c22183385d94aadb32e5dc88092cd0fef4","06ad3c03823012e1f51b52bb889a5537bc9888e8ea5af6e74b73e70f94bf7673","fdd5e8f6ae0db817be0b71da20498c1806968d8a6459559c249f322fa73464a7","c5fadeb5d90d68baffc631455a07ca340ccf1e31110955e16d45eb5f87147cd9","9be21611a341426e9146257c54179e22d178bb7d4106e247ddf3e507b7985a6b","7cb50cccb250a63f6d8a912e58691035417b1c1b8fc01efae566cd5600ce3fce","4657dfe8965be8980a93072bcfb5e59a65124406db0f819215ee78ba47934b3e","aed193be7ba0de293d13c1981e4fe351e44b70b4a57c07eceace2bea1a242155","ed726c13dc49451de3a966cb873474a95bfa7c3d171811aa78a51ec4abd3a76b","f5fd754857046f37eae58c982d7a0991ba08c996f5b3390fa2bad47ef2718ded","870744363b1a5986d6773b5706dde258c039f6d34a5ffc270915033a6a67c82c","06b7819d7f1c7f5472118266ed7bca8785dceae09e36ea3a4af665c6d1d8327c","3bf0c63fcb93463407af97a5e5ee64fa883d107ef9e558472c4eb9aaaefa459d","8685ebef665338dd6931e2ccdf3c19d9f0e5a1067c918f22e7081c2558f8faf8","3efdaebb1d8923ebd99c9e7ace3b4194ab45512e2be79c1b7d68d9243e0d2681","de6c63ab90779f1d0aeaf9ed9bf7d3a779bece1d3ca97e21718b13acd028389b","e8d67c435a4a59304e1414280e952efe17be4254fca27916bf63f9f73e54aba4"],"limit":5000,"since":1732911768},{"limit":5000,"since":1732911768,"#t":["nostr","asknostr","plebs","jobstr"],"kinds":[34235,30023,5,9802,10002,0,6,1,30311]}]', true);
//    $events = $store(nostriphant\Transpher\Nostr\Subscription::make(...$filter_prototypes));
//    expect($sqlite->lastErrorMsg())->toBe('not an error');
//
//    foreach ($events as $event) {
//        expect($event->id)->not()->toBeNull();
//    }
//});

it('can check if an event exists', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\Stores\SQLite($sqlite, Mockery::spy(\Psr\Log\LoggerInterface::class));
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->exec("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                    . "'07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',"
                    . "'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',"
                    . "1731082493,"
                    . "5,"
                    . "'',"
                    . "'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44'"
                    . ")"))->toBeTrue();

    expect(isset($store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']))->toBeTrue();
});

it('can retrieve an event with tags added without a specific position', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\Stores\SQLite($sqlite, Mockery::spy(\Psr\Log\LoggerInterface::class));
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->exec("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                    . "'07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',"
                    . "'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',"
                    . "1731082493,"
                    . "5,"
                    . "'',"
                    . "'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44'"
                    . ")"))->toBeTrue();
    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'p')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (1, 'first-value')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (1, 'second-value')");
    $sqlite->exec("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE event.id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'");

    $event = $store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'];
    expect($event->tags)->toHaveCount(1);
    expect($event->tags[0])->toBe(['p', 'first-value', 'second-value']);
});

it('can retrieve an event with a tag', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\Stores\SQLite($sqlite, Mockery::spy(\Psr\Log\LoggerInterface::class));
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->exec("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                    . "'07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',"
                    . "'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',"
                    . "1731082493,"
                    . "5,"
                    . "'',"
                    . "'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44'"
                    . ")"))->toBeTrue();
    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'p')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, position, value) VALUES (1, 2, 'second-value')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, position, value) VALUES (1, 1, 'first-value')");
    $sqlite->exec("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE event.id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'");

    $event = $store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'];
    expect($event->tags)->toHaveCount(1);
    expect($event->tags[0])->toBe(['p', 'first-value', 'second-value']);
});


it('can retrieve an event with tags', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\Stores\SQLite($sqlite, Mockery::spy(\Psr\Log\LoggerInterface::class));
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->exec("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                    . "'07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',"
            . "'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',"
            . "1731082493,"
            . "5,"
            . "'',"
            . "'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44'"
            . ")"))->toBeTrue();

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'e')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (1, 'b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'L')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (2, 'pink.momostr')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'k')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (3, '1')");

    $sqlite->exec("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE event.id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'");

    $event = $store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'];
    expect($event->pubkey)->toBe('a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc');
    expect($event->created_at)->toBe(1731082493);
    expect($event->kind)->toBe(5);
    expect($event->content)->toBe('');
    expect($event->sig)->toBe('ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44');
    expect($event->tags)->toHaveCount(3);

    expect(nostriphant\NIP01\Event::extractTagValues($event, 'e')[0])->toBe(['b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9']);
    expect(nostriphant\NIP01\Event::extractTagValues($event, 'L')[0])->toBe(['pink.momostr']);
    expect(nostriphant\NIP01\Event::extractTagValues($event, 'k')[0])->toBe(['1']);
});



it('can store an event with tags', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\Stores\SQLite($sqlite, Mockery::spy(\Psr\Log\LoggerInterface::class));
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->querySingle("SELECT id FROM event WHERE id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBeNull();
    expect($sqlite->querySingle("SELECT COUNT(id) FROM tag WHERE event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(0);
    expect($sqlite->querySingle("SELECT COUNT(tag_value.id) FROM tag LEFT JOIN tag_value ON tag.id = tag_value.tag_id WHERE tag.event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(0);

    $store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'] = \nostriphant\NIP01\Event::__set_state([
        'id' => '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',
        'pubkey' => 'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',
        'created_at' => 1731082493,
        'kind' => 5,
        'content' => '',
        'sig' => 'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44',
        'tags' => [
            0 => [
                0 => 'e',
                1 => 'b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9',
            ],
            1 => [
                0 => 'L',
                1 => 'pink.momostr',
            ],
            2 => [
                0 => 'k',
                1 => '1',
            ],
        ],
    ]);

    expect($sqlite->querySingle("SELECT id FROM event WHERE id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');

    expect($sqlite->querySingle("SELECT COUNT(id) FROM tag WHERE event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(3);
    expect($sqlite->querySingle("SELECT COUNT(tag_value.id) FROM tag LEFT JOIN tag_value ON tag.id = tag_value.tag_id WHERE tag.event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(3);
});


it('can delete an event with tags', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\Stores\SQLite($sqlite, Mockery::spy(\Psr\Log\LoggerInterface::class));
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->exec("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                    . "'07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',"
                    . "'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',"
                    . "1731082493,"
                    . "5,"
                    . "'',"
                    . "'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44'"
                    . ")"))->toBeTrue();

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'e')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (1, 'b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'L')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (2, 'pink.momostr')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'k')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (3, '1')");

    $sqlite->exec("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE event.id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'");

    unset($store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']);

    expect($sqlite->querySingle("SELECT id FROM event WHERE id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBeNull();
    expect($sqlite->querySingle("SELECT COUNT(id) FROM tag WHERE event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(0);
    expect($sqlite->querySingle("SELECT COUNT(tag_value.id) FROM tag LEFT JOIN tag_value ON tag.id = tag_value.tag_id WHERE tag.event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(0);
});

it('can count events', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\Stores\SQLite($sqlite, Mockery::spy(\Psr\Log\LoggerInterface::class));
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->exec("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                    . "'07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',"
                    . "'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',"
                    . "1731082493,"
                    . "5,"
                    . "'',"
                    . "'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44'"
                    . ")"))->toBeTrue();

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'e')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (1, 'b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'L')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (2, 'pink.momostr')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'k')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (3, '1')");

    $sqlite->exec("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE event.id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'");

    expect($store)->toHaveCount(1);
});

it('can filter events', function (array $filter_prototype) {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\Stores\SQLite($sqlite, Mockery::spy(\Psr\Log\LoggerInterface::class));
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->exec("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                    . "'07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',"
                    . "'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',"
                    . "1731082493,"
                    . "5,"
                    . "'',"
                    . "'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44'"
                    . ")"))->toBeTrue();

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'e')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (1, 'b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'L')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (2, 'pink.momostr')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'k')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (3, '1')");

    $sqlite->exec("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE event.id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'");

    $statement = $sqlite->prepare("SELECT id, pubkey, created_at, kind, content, sig FROM event WHERE (id IN (?))");
    $statement->bindValue(1, '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');
    $result = $statement->execute();
    $data = $result->fetchArray();
    expect($data['id'])->toBe('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');

    $events = iterator_to_array($store(\nostriphant\Transpher\Nostr\Subscription::make($filter_prototype)));
    expect($sqlite->lastErrorMsg())->toBe('not an error');
    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(nostriphant\NIP01\Event::class);
    expect($events[0]->id)->toBe('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');
})->with([
    [['ids' => ['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']]],
    [['authors' => ['a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc']]],
    [['since' => 1731082480]],
    [['until' => 1731082500]],
    [['#e' => ['b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9']]],
    [['#L' => ['pink.momostr']]],
    [['#k' => ['1']]]
]);

it('can limit events in result set', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\Stores\SQLite($sqlite, Mockery::spy(\Psr\Log\LoggerInterface::class));
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->exec("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                    . "'07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',"
                    . "'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',"
                    . "1731082493,"
                    . "5,"
                    . "'',"
                    . "'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44'"
                    . ")"))->toBeTrue();

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'e')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (1, 'b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'L')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (2, 'pink.momostr')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'k')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (3, '1')");

    $sqlite->exec("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE event.id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'");

    $statement = $sqlite->prepare("SELECT id, pubkey, created_at, kind, content, sig FROM event WHERE (id IN (?))");
    $statement->bindValue(1, '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');
    $result = $statement->execute();
    $data = $result->fetchArray();
    expect($data['id'])->toBe('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');

    $events = iterator_to_array($store(\nostriphant\Transpher\Nostr\Subscription::make(['ids' => ['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'], 'limit' => 1])));
    expect($events)->toHaveCount(1);

    $events = iterator_to_array($store(\nostriphant\Transpher\Nostr\Subscription::make(['ids' => ['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'], 'limit' => 0])));
    expect($events)->toHaveCount(0);
});
