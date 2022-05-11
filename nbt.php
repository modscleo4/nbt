<?php

/**
 * Copyright 2022 Dhiego Cassiano Fogaça Barbosa

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Modscleo4\NBT;

require_once __DIR__ . '/vendor/autoload.php';

use \Modscleo4\NBT\Lib\NBTParser;

if ($argc < 2) {
    echo "Usage: {$argv[0]} [--snbt] <file>\n";
    exit(1);
}

$file = $argv[1];

$snbt = false;
if ($argv[1] == '--snbt') {
    $snbt = true;
    $file = $argv[2];
}

// Load the file
$data = file_get_contents($file);

if ($snbt) {
    $nbt = NBTParser::parseSNBT($data);
    print($nbt);
} else {
    // gzip decompress
    $data = gzdecode($data);

    $nbt = NBTParser::parse($data);
    print($nbt);
}

exit(0);
