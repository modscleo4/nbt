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

enum NBTTagType: int
{
    case TAG_End = 0;
    case TAG_Byte = 1;
    case TAG_Short = 2;
    case TAG_Int = 3;
    case TAG_Long = 4;
    case TAG_Float = 5;
    case TAG_Double = 6;
    case TAG_Byte_Array = 7;
    case TAG_String = 8;
    case TAG_List = 9;
    case TAG_Compound = 10;
    case TAG_Int_Array = 11;
    case TAG_Long_Array = 12;

    public function size(): int
    {
        switch ($this) {
            case self::TAG_End:
                return 0;

            case self::TAG_Byte:
                return 1;

            case self::TAG_Short:
                return 2;

            case self::TAG_Int:
                return 4;

            case self::TAG_Long:
                return 8;

            case self::TAG_Float:
                return 4;

            case self::TAG_Double:
                return 8;
        }

        return -1;
    }

    public function asString(): string
    {
        switch ($this) {
            case self::TAG_End:
                return 'End';

            case self::TAG_Byte:
                return 'Byte';

            case self::TAG_Short:
                return 'Short';

            case self::TAG_Int:
                return 'Int';

            case self::TAG_Long:
                return 'Long';

            case self::TAG_Float:
                return 'Float';

            case self::TAG_Double:
                return 'Double';

            case self::TAG_Byte_Array:
                return 'ByteArray';

            case self::TAG_String:
                return 'String';

            case self::TAG_List:
                return 'List';

            case self::TAG_Compound:
                return 'Compound';

            case self::TAG_Int_Array:
                return 'IntArray';

            case self::TAG_Long_Array:
                return 'LongArray';

        }
    }
}

class NBTTag implements \JsonSerializable
{
    protected array $additionalMetadata = [];

    public function __construct(protected NBTTagType $type, protected string $name, protected $payload)
    {
        if ($type === NBTTagType::TAG_List) {
            $this->additionalMetadata['listType'] = array_pop($this->payload);
        }
    }

    public function getType(): NBTTagType
    {
        return $this->type;
    }

    public function jsonSerialize(): mixed
    {
        if ($this->type === NBTTagType::TAG_End) {
            return [
                'type' => $this->type->asString()
            ];
        }

        return array_merge($this->additionalMetadata, [
            'type' => $this->type->asString(),
            'name' => $this->name,
            'payload' => $this->payload
        ]);
    }

    private function toSNBT($iteration = 1): string
    {
        switch ($this->type) {
            case NBTTagType::TAG_End:
                return '';

            case NBTTagType::TAG_Byte:
                return $this->payload . 'b';

            case NBTTagType::TAG_Short:
                return $this->payload . 's';

            case NBTTagType::TAG_Int:
                return $this->payload;

            case NBTTagType::TAG_Long:
                return $this->payload . 'l';

            case NBTTagType::TAG_Float:
                return $this->payload . 'f';

            case NBTTagType::TAG_Double:
                return $this->payload . 'd';

            case NBTTagType::TAG_Byte_Array:
                return "[B;\n" . str_pad('', $iteration * 2, ' ') . implode(",\n" . str_pad('', $iteration * 2, ' '), $this->payload) . "\n]";

            case NBTTagType::TAG_String:
                return '"' . addslashes($this->payload) . '"';

            case NBTTagType::TAG_List:
                return "[\n" . str_pad('', $iteration * 2, ' ') . implode(",\n" . str_pad('', $iteration * 2, ' '), array_map(function ($tag) use ($iteration) {
                    if ($tag instanceof NBTTag) {
                        return $tag->toSNBT($iteration + 1);
                    }

                    return $tag->__toString();
                }, $this->payload)) . "\n" . str_pad('', ($iteration - 1) * 2, ' ') . "]";

            case NBTTagType::TAG_Compound:
                return "{\n" . str_pad('', $iteration * 2, ' ') . implode(",\n" . str_pad('', $iteration * 2, ' '), array_map(function ($tag) use ($iteration) {
                    return $tag->name . ': ' . $tag->toSNBT($iteration + 1);
                }, $this->payload)) . "\n" . str_pad('', ($iteration - 1) * 2, ' ') . "}";

            case NBTTagType::TAG_Int_Array:
                return "[I;\n" . str_pad('', $iteration * 2, ' ') . implode(",\n" . str_pad('', $iteration * 2, ' '), $this->payload) . "\n]";

            case NBTTagType::TAG_Long_Array:
                return "[L;\n" . str_pad('', $iteration * 2, ' ') . implode(",\n" . str_pad('', $iteration * 2, ' '), $this->payload) . "\n]";
        }
    }

    public function __toString(): string
    {
        return $this->toSNBT();
    }

    public function getByteLength(): int
    {
        if ($this->type === NBTTagType::TAG_End) {
            return 1;
        }

        $payloadSize = $this->type->size();
        if ($payloadSize === -1) {
            switch ($this->type) {
                case NBTTagType::TAG_Byte_Array:
                    $payloadSize = 2 + count($this->payload);
                    break;
                case NBTTagType::TAG_String:
                    $payloadSize = 2 + strlen($this->payload);
                    break;
                case NBTTagType::TAG_List:
                    $payloadSize = 1 + 4 + array_reduce($this->payload, function ($carry, $item) {
                        // TAG_List elements don't have neither type nor name
                        return $carry + $item->getByteLength() - 1 - 2;
                    }, 0);
                    break;
                case NBTTagType::TAG_Compound:
                    $payloadSize = array_reduce($this->payload, function ($carry, $item) {
                        return $carry + $item->getByteLength();
                    }, 0) + 1;
                    break;
                case NBTTagType::TAG_Int_Array:
                    $payloadSize = 4 + 4 * count($this->payload);
                    break;
                case NBTTagType::TAG_Long_Array:
                    $payloadSize = 4 + 8 * count($this->payload);
                    break;
            }

        }

        return 1 + 2 + strlen($this->name ?? "") + $payloadSize;
    }
}

class NBT
{
    public const DEBUG = false;

    public static function parse(string $nbtStr, $iteration = 0): NBTTag
    {
        $tagId = unpack('C', $nbtStr[0])[1];
        $tag = NBTTagType::from($tagId);
        if ($tag === NBTTagType::TAG_End) {
            return new NBTTag($tag, '', null);
        }

        $nameLength = unpack('n', substr($nbtStr, 1, 2))[1];
        $name = substr($nbtStr, 3, $nameLength);
        $data = substr($nbtStr, 3 + $nameLength);

        if (self::DEBUG) {
            echo(str_pad('> ', 2 + $iteration * 2, ' ', STR_PAD_LEFT) . "Parsing tag [{$tagId}] {$name}...\n");
        }

        $payload = self::parseTag($tag, $data, $iteration);

        if (self::DEBUG) {
            echo(str_pad('> ', 2 + $iteration * 2, ' ', STR_PAD_LEFT) . "[{$name}] Done.\n");
        }

        return new NBTTag($tag, $name, $payload);
    }

    private static function parseTag(NBTTagType $tag, string $data, $iteration = 0): mixed
    {
        switch ($tag) {
            // 1 byte / 8 bits, signed
            case NBTTagType::TAG_Byte:
                return unpack('c', substr($data, 0, 1))[1];

            // 2 bytes / 16 bits, signed
            case NBTTagType::TAG_Short:
                $value = strrev(substr($data, 0, 2));
                return unpack('s', $value)[1];

            // 4 bytes / 32 bits, signed
            case NBTTagType::TAG_Int:
                $value = strrev(substr($data, 0, 4));
                return unpack('l', $value)[1];

            // 8 bytes / 64 bits, signed
            case NBTTagType::TAG_Long:
                $value = strrev(substr($data, 0, 8));
                return unpack('q', $value)[1];

            // 4 bytes / 32 bits, signed, big endian, IEEE 754-2008, binary32
            case NBTTagType::TAG_Float:
                return unpack('G', substr($data, 0, 4))[1];

            // 8 bytes / 64 bits, signed, big endian, IEEE 754-2008, binary64
            case NBTTagType::TAG_Double:
                return unpack('E', substr($data, 0, 8))[1];

            // TAG_Int's payload size, then size TAG_Byte's payloads.
            case NBTTagType::TAG_Byte_Array:
            {
                //global $payload;
                $payload = [];

                $payloadLength = unpack('l', strrev(substr($data, 0, 4)))[1];
                $payloadStr = substr($data, 4, $payloadLength);

                for ($i = 0; $i < $payloadLength; $i++) {
                    $payload[] = self::parseTag(NBTTagType::TAG_Byte, $payloadStr[$i], $iteration + 1);
                }

                return $payload;
            }

            // A TAG_Short-like, but instead unsigned payload length, then a UTF-8 string resembled by length bytes.
            case NBTTagType::TAG_String:
            {
                $payloadLength = unpack('n', substr($data, 0, 2))[1];
                return substr($data, 2, $payloadLength);
            }

            // TAG_Byte's payload tagId, then TAG_Int's payload size, then size tags' payloads, all of type tagId.
            case NBTTagType::TAG_List:
            {
                $payload = [];

                $subtagId = unpack('c', substr($data, 0, 1))[1];
                $subtag = NBTTagType::from($subtagId);
                $payloadLength = unpack('l', strrev(substr($data, 1, 4)))[1];
                $payloadStr = substr($data, 5);

                $j = 0;
                for ($i = 0; $i < $payloadLength; $i++) {
                    $str = substr($payloadStr, $j);
                    $_tag = new NBTTag($subtag, '', self::parseTag($subtag, $str, $iteration + 1));
                    $payload[] = $_tag;

                    $j += $_tag->getByteLength() - 1 - 2;
                }

                $payload[] = $subtag;

                return $payload;
            }

            // Fully formed tags, followed by a TAG_End.
            case NBTTagType::TAG_Compound:
            {
                $payload = [];

                $i = 0;
                while (($tag = self::parse(substr($data, $i), $iteration + 1))->getType() != NBTTagType::TAG_End) {
                    $payload[] = $tag;
                    $i += $tag->getByteLength();
                }

                return $payload;
            }

            // TAG_Int's payload size, then size TAG_Int's payloads.
            case NBTTagType::TAG_Int_Array:
            {
                $payload = [];

                $payloadLength = unpack('l', strrev(substr($data, 0, 4)))[1];
                for ($i = 0; $i < $payloadLength; $i++) {
                    $payload[] = self::parseTag(NBTTagType::TAG_Int, substr($data, 4 + $i * 4, 4), $iteration + 1);
                }

                return $payload;
            }

            // TAG_Int's payload size, then size TAG_Long's payloads.
            case NBTTagType::TAG_Long_Array:
            {
                $payload = [];

                $payloadLength = unpack('l', strrev(substr($data, 0, 4)))[1];
                for ($i = 0; $i < $payloadLength; $i++) {
                    $payload[] = self::parseTag(NBTTagType::TAG_Long, substr($data, 4 + $i * 8, 8), $iteration + 1);
                }

                return $payload;
            }
        }
    }
}

if ($argc < 2) {
    echo "Usage: {$argv[0]} <file>\n";
    exit(1);
}

$file = $argv[1];

// Load the file
$data = file_get_contents($file);

// gzip decompress
$data = gzdecode($data);

$nbt = NBT::parse($data);
print_r($nbt->__toString());

exit(0);
