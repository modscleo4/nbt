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
    echo "Usage: {$argv[0]} [--snbt] [--out=<file>] [--debug] [--no-format] [--bin] <file>\n";
    exit(1);
}

$i = 1;
$args = getopt('', ['snbt', 'out::', 'debug', 'no-format', 'bin'], $i);

$file = $argv[$i];

$snbt = array_key_exists('snbt', $args);
$print = !array_key_exists('out', $args);
$debug = array_key_exists('debug', $args);
$format = !array_key_exists('no-format', $args);
$bin = array_key_exists('bin', $args);

// Load the file
$data = file_get_contents($file);

/** @var NBTTag */
$nbt = null;

NBTParser::$DEBUG = $debug;
NBTParser::$FORMAT = $format;

if ($snbt) {
    $nbt = NBTParser::parseSNBT($data);
} else {
    // gzip decompress
    $data = gzdecode($data);

    $nbt = NBTParser::parse($data);
}

if ($bin) { // GZip compressed NBT Binary format
    $nbt = gzencode($nbt->toBinary(), 7);
}

if ($print) {
    print($nbt);
} else {
    $f = $args['out'];
    if (!$f) {
        $f = $file . ($bin ? '.dat' : '.snbt');
    }

    file_put_contents($f, $nbt);
}

exit(0);
