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
    echo "Usage: {$argv[0]} [--snbt] [--out=<file>] [--debug] [--no-format] [--bin] [--interactive] <file>\n";
    exit(1);
}

$i = 1;
$args = getopt('', ['snbt', 'out::', 'debug', 'no-format', 'bin', 'interactive'], $i);

$file = $argv[$i];

$snbt = array_key_exists('snbt', $args);
$print = !array_key_exists('out', $args);
$debug = array_key_exists('debug', $args);
$format = !array_key_exists('no-format', $args);
$bin = array_key_exists('bin', $args);
$interactive = array_key_exists('interactive', $args);

// Load the file
$data = file_get_contents($file);

if (!$data) {
    echo "Error: Could not read file '{$file}'\n";
    exit(1);
}

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

if ($interactive) {
    function prompt(string $message): string {
        print($message);
        return fgets(STDIN);
    }

    while (($line = prompt('> ')) !== false) {
        $line = trim($line);

        $parts = preg_split('/(\'[^\']*\')|\h+/', $line, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $command = array_shift($parts);

        try {
            switch ($command) {
                case 'keys': {
                    if (!($nbt instanceof \Modscleo4\NBT\Lib\Tag\NBTTagCompound)) {
                        error_log('Cannot continue: tag is not Compound.');
                        exit(1);
                    }

                    $key = $parts[0] ?? '';

                    $tag = $nbt->get($key);
                    if (!($tag instanceof \Modscleo4\NBT\Lib\Tag\NBTTagCompound)) {
                        error_log('Cannot continue: tag is not Compound.');
                        break;
                    }

                    print(implode(', ', array_map(function ($v) {
                        return "{$v['name']} ({$v['type']})";
                    }, $tag->keys())) . "\n");

                    break;
                }

                case 'get': {
                    if (!($nbt instanceof \Modscleo4\NBT\Lib\Tag\NBTTagCompound)) {
                        error_log('Cannot continue: tag is not Compound.');
                        exit(1);
                    }

                    $key = $parts[0] ?? '';
                    print($nbt->get($key) . "\n");

                    break;
                }

                case 'set': {
                    if (!($nbt instanceof \Modscleo4\NBT\Lib\Tag\NBTTagCompound)) {
                        error_log('Cannot continue: tag is not Compound.');
                        exit(1);
                    }

                    if (!isset($parts[0])) {
                        error_log('Cannot continue: missing key.');
                        break;
                    }

                    if (!isset($parts[1])) {
                        error_log('Cannot continue: missing value.');
                        break;
                    }

                    $key = $parts[0];
                    $value = NBTParser::parseSNBT($parts[1]);
                    print($nbt->set($key, $value) . "\n");

                    break;
                }

                case 'exit':
                    print("Bye.\n");
                    break 2;

                default:
                    print("Unknown command.\n\n");
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
    }
} else {
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
}

exit(0);
