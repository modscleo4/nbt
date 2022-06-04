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

namespace Modscleo4\NBT\Lib;

use Modscleo4\NBT\Lib\Tag\NBTTagByte;
use Modscleo4\NBT\Lib\Tag\NBTTagByteArray;
use Modscleo4\NBT\Lib\Tag\NBTTagCompound;
use Modscleo4\NBT\Lib\Tag\NBTTagDouble;
use Modscleo4\NBT\Lib\Tag\NBTTagEnd;
use Modscleo4\NBT\Lib\Tag\NBTTagFloat;
use Modscleo4\NBT\Lib\Tag\NBTTagInt;
use Modscleo4\NBT\Lib\Tag\NBTTagIntArray;
use Modscleo4\NBT\Lib\Tag\NBTTagList;
use Modscleo4\NBT\Lib\Tag\NBTTagLong;
use Modscleo4\NBT\Lib\Tag\NBTTagLongArray;
use Modscleo4\NBT\Lib\Tag\NBTTagShort;
use Modscleo4\NBT\Lib\Tag\NBTTagString;

class NBTParser
{
    public static $DEBUG = false;
    public static $FORMAT = true;

    public static function parse(string $nbtStr, int $iteration = 0): NBTTag
    {
        $tagId = unpack('C', $nbtStr[0])[1];
        $tag = NBTTagType::from($tagId);
        if ($tag === NBTTagType::TAG_End) {
            return new NBTTagEnd();
        }

        $nameLength = unpack('n', substr($nbtStr, 1, 2))[1];
        $name = substr($nbtStr, 3, $nameLength);
        $data = substr($nbtStr, 3 + $nameLength);

        if (self::$DEBUG) {
            echo (str_pad('> ', 2 + $iteration * 2, ' ', STR_PAD_LEFT) . "Parsing tag [{$tag->asString()}]" . (!empty($name) ? " [name={$name}]" : '') . "...\n");
        }

        $nbtTag = self::parseTag($tag, $name, $data, $iteration);

        if (self::$DEBUG) {
            echo (str_pad('> ', 2 + $iteration * 2, ' ', STR_PAD_LEFT) . "[{$tag->asString()}] " . (!empty($name) ? "[name={$name}] " : '') . "Done.\n");
        }

        return $nbtTag;
    }

    private static function parseTag(NBTTagType $tag, string $name, string $data, int $iteration = 0): NBTTag
    {
        $payload = null;

        switch ($tag) {
            // 1 byte / 8 bits, signed
            case NBTTagType::TAG_Byte:
                $payload = unpack('c', substr($data, 0, 1))[1];

                return new NBTTagByte($name, $payload);

            // 2 bytes / 16 bits, signed
            case NBTTagType::TAG_Short:
                $value = strrev(substr($data, 0, 2));
                $payload = unpack('s', $value)[1];

                return new NBTTagShort($name, $payload);

            // 4 bytes / 32 bits, signed
            case NBTTagType::TAG_Int:
                $value = strrev(substr($data, 0, 4));
                $payload = unpack('l', $value)[1];

                return new NBTTagInt($name, $payload);

            // 8 bytes / 64 bits, signed
            case NBTTagType::TAG_Long:
                $value = strrev(substr($data, 0, 8));
                $payload = unpack('q', $value)[1];

                return new NBTTagLong($name, $payload);

            // 4 bytes / 32 bits, signed, big endian, IEEE 754-2008, binary32
            case NBTTagType::TAG_Float:
                $payload = unpack('G', substr($data, 0, 4))[1];

                return new NBTTagFloat($name, $payload);

            // 8 bytes / 64 bits, signed, big endian, IEEE 754-2008, binary64
            case NBTTagType::TAG_Double:
                $payload = unpack('E', substr($data, 0, 8))[1];

                return new NBTTagDouble($name, $payload);

            // TAG_Int's payload size, then size TAG_Byte's payloads.
            case NBTTagType::TAG_Byte_Array: {
                $payload = [];

                $payloadLength = unpack('l', strrev(substr($data, 0, 4)))[1];

                for ($i = 0; $i < $payloadLength; $i++) {
                    $payload[] = self::parseTag(NBTTagType::TAG_Byte, '', substr($data, 4 + $i, 1), $iteration + 1);
                }

                return new NBTTagByteArray($name, $payload);
            }

            // A TAG_Short-like, but instead unsigned payload length, then a UTF-8 string resembled by length bytes.
            case NBTTagType::TAG_String: {
                $payloadLength = unpack('n', substr($data, 0, 2))[1];
                $payload = substr($data, 2, $payloadLength);

                return new NBTTagString($name, $payload);
            }

            // TAG_Byte's payload tagId, then TAG_Int's payload size, then size tags' payloads, all of type tagId.
            case NBTTagType::TAG_List: {
                $payload = [];

                $subtagId = unpack('c', substr($data, 0, 1))[1];
                $subtag = NBTTagType::from($subtagId);
                $payloadLength = unpack('l', strrev(substr($data, 1, 4)))[1];
                $payloadStr = substr($data, 5);

                $j = 0;
                for ($i = 0; $i < $payloadLength; $i++) {
                    $str = substr($payloadStr, $j);
                    $_tag = self::parseTag($subtag, '', $str, $iteration + 1);
                    $payload[] = $_tag;

                    $j += $_tag->getByteLength() - 1 - 2;
                }

                return new NBTTagList($name, $payload, [
                    'listType' => $subtag
                ]);
            }

            // Fully formed tags, followed by a TAG_End.
            case NBTTagType::TAG_Compound: {
                $payload = [];

                $i = 0;
                while (($tag = self::parse(substr($data, $i), $iteration + 1))->getType() != NBTTagType::TAG_End) {
                    /** @var NBTNamedTag $tag */

                    $payload[$tag->getName()] = $tag;
                    $i += $tag->getByteLength();
                }

                return new NBTTagCompound($name, $payload);
            }

            // TAG_Int's payload size, then size TAG_Int's payloads.
            case NBTTagType::TAG_Int_Array: {
                $payload = [];

                $payloadLength = unpack('l', strrev(substr($data, 0, 4)))[1];
                for ($i = 0; $i < $payloadLength; $i++) {
                    $payload[] = self::parseTag(NBTTagType::TAG_Int, '', substr($data, 4 + $i * 4, 4), $iteration + 1);
                }

                return new NBTTagIntArray($name, $payload);
            }

            // TAG_Int's payload size, then size TAG_Long's payloads.
            case NBTTagType::TAG_Long_Array: {
                $payload = [];

                $payloadLength = unpack('l', strrev(substr($data, 0, 4)))[1];
                for ($i = 0; $i < $payloadLength; $i++) {
                    $payload[] = self::parseTag(NBTTagType::TAG_Long, '', substr($data, 4 + $i * 8, 8), $iteration + 1);
                }

                return new NBTTagLongArray($name, $payload);
            }
        }
    }

    public static function parseSNBT(string $snbtStr): NBTTag
    {
        $snbtStr = trim($snbtStr);

        return self::parseSNBTTag($snbtStr);
    }

    /**
     * Since this is parsing a SNBT, we can assume that no tag will be TAG_End.
     *
     * @param string $data
     * @param string $name
     * @param NBTTagType|null $forceType
     *
     * @return NBTNamedTag
     */
    private static function parseSNBTTag(string $data, string $name = '', int $iteration = 0, NBTTagType $forceType = null): NBTNamedTag
    {
        if (self::$DEBUG) {
            echo (str_pad('> ', 2 + $iteration * 2, ' ', STR_PAD_LEFT) . "Parsing tag" . (!empty($name) ? " [name={$name}]" : '') . "...\n");
        }

        try {
            $i = 0;
            while ($data[$i] === ' ' || $data[$i] === "\t" || $data[$i] === "\n" || $data[$i] === "\r") {
                $i++;
            }

            switch ($data[$i]) {
                case '[': {
                    $payload = [];

                    $j = $i + 1;
                    while ($data[$j] === ' ' || $data[$j] === "\t" || $data[$j] === "\n" || $data[$j] === "\r") {
                        $j++;
                    }

                    $_tag = null;
                    if (($data[$j] === 'B' || $data[$j] === 'I' || $data[$j] === 'L') && $data[$j + 1] === ';') {
                        switch ($data[$j]) {
                            case 'B':
                                $_tag = NBTTagType::TAG_Byte;
                                break;

                            case 'I':
                                $_tag = NBTTagType::TAG_Int;
                                break;

                            case 'L':
                                $_tag = NBTTagType::TAG_Long;
                                break;
                        }

                        $j += 2;
                    }

                    $k = $j;
                    while ($data[$k] != ']') {
                        while ($data[$k] === ' ' || $data[$k] === "\t" || $data[$k] === "\n" || $data[$k] === "\r") {
                            $k++;
                        }

                        if ($data[$k] === ']') {
                            break;
                        }

                        if ($data[$k] === ',') {
                            $k++;
                            continue;
                        }

                        $tag = self::parseSNBTTag(substr($data, $k), '', $iteration + 1, $_tag);
                        $k += $tag->getAdditionalMetadata()['byteLength'];

                        $payload[] = $tag;
                    }

                    switch (substr($data, $i + 1, 2)) {
                        case 'B;':
                            return new NBTTagByteArray($name, $payload, ['byteLength' => $k - $i + 4]);

                        case 'I;':
                            return new NBTTagIntArray($name, $payload, ['byteLength' => $k - $i + 4]);

                        case 'L;':
                            return new NBTTagLongArray($name, $payload, ['byteLength' => $k - $i + 4]);
                    }

                    // Minecraft uses TAG_End for empty lists.
                    return new NBTTagList($name, $payload, ['listType' => isset($payload[0]) ? $payload[0]->getType() : NBTTagType::TAG_End, 'byteLength' => $k - $i + 2]);
                }

                case '{': {
                    $payload = [];

                    $j = $i + 1;
                    while ($data[$j] != '}') {
                        while ($data[$j] === ' ' || $data[$j] === "\t" || $data[$j] === "\n" || $data[$j] === "\r") {
                            $j++;
                        }

                        if ($data[$j] === '}') {
                            break;
                        }

                        if ($data[$j] === ',') {
                            $j++;
                            continue;
                        }

                        if ($data[$j] === '"') {
                            $j++;
                            $k = $j;
                            while ($data[$k] !== '"') {
                                $k++;
                            }
                        } else {
                            $k = $j;
                            while ($data[$k] !== ':') {
                                $k++;
                            }
                        }

                        $tagName = substr($data, $j, $k - $j);
                        if ($data[$k] === '"') {
                            $k++;
                        }

                        $j = $k + 1;
                        while ($data[$j] === ' ' || $data[$j] === "\t" || $data[$j] === "\n" || $data[$j] === "\r") {
                            $j++;
                        }

                        $tag = self::parseSNBTTag(substr($data, $j), $tagName, $iteration + 1);
                        $j += $tag->getAdditionalMetadata()['byteLength'];

                        $payload[$tag->getName()] = $tag;
                    }

                    return new NBTTagCompound($name, $payload, ['byteLength' => $j - $i + 2]);
                }

                case '"': {
                    $j = $i + 1;
                    while ($data[$j] != '"' || $data[$j - 1] === "\\") {
                        $j++;
                    }

                    return new NBTTagString($name, stripslashes(substr($data, $i + 1, $j - 1)), ['byteLength' => $j - 1 + 2]);
                }

                case "'": {
                    $j = $i + 1;
                    while ($data[$j] != "'" || $data[$j - 1] === "\\") {
                        $j++;
                    }

                    return new NBTTagString($name, stripslashes(substr($data, $i + 1, $j - 1)), ['byteLength' => $j - 1 + 2]);
                }

                default:
                    $j = $i;

                    $k = $j + 1;
                    while (ctype_digit($data[$k]) || $data[$k] === '-' || $data[$k] === '+' || $data[$k] === '.') {
                        $k++;
                    }

                    $num = substr($data, $j, $k - $j);

                    switch ($data[$k]) {
                        case 'b':
                        case 'B':
                            return new NBTTagByte($name, (int) $num, ['byteLength' => $k - $j + 1]);

                        case 's':
                        case 'S':
                            return new NBTTagShort($name, (int) $num, ['byteLength' => $k - $j + 1]);

                        case 'l':
                        case 'L':
                            return new NBTTagLong($name, (int) $num, ['byteLength' => $k - $j + 1]);

                        case 'f':
                        case 'F':
                            return new NBTTagFloat($name, (float) $num, ['byteLength' => $k - $j + 1]);

                        case 'd':
                        case 'D':
                            return new NBTTagDouble($name, (float) $num, ['byteLength' => $k - $j + 1]);
                    }

                    if ($forceType === NBTTagType::TAG_Int || $forceType === null && is_int($num + 0)) {
                        return new NBTTagInt($name, (int) $num, ['byteLength' => $k - $j]);
                    }

                    return new NBTTagDouble($name, (float) $num, ['byteLength' => $k - $j]);
            }

            return new NBTTagEnd($name, []);
        } finally {
            if (self::$DEBUG) {
                echo (str_pad('> ', 2 + $iteration * 2, ' ', STR_PAD_LEFT) . "" . (!empty($name) ? "[name={$name}] " : '') . "Done.\n");
            }
        }
    }
}
