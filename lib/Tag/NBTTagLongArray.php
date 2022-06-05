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

namespace Modscleo4\NBT\Lib\Tag;

use Modscleo4\NBT\Lib\NBTNamedTag;
use Modscleo4\NBT\Lib\NBTTagType;

/**
 * @method array getPayload()
 */
class NBTTagLongArray extends NBTNamedTag
{
    protected NBTTagType $type = NBTTagType::TAG_Long_Array;

    public function toSNBT(bool $format = true, int $iteration = 1): string
    {
        if (!$format) {
            return '[L;' . implode(',', $this->getPayload()) . ']';
        }

        return "[L;\n" . str_pad('', $iteration * 2, ' ') . implode(",\n" . str_pad('', $iteration * 2, ' '), $this->getPayload()) . "\n" . str_pad('', ($iteration - 1) * 2, ' ') . "]";
    }

    protected function payloadAsBinary(): string
    {
        return strrev(pack('l', sizeof($this->getPayload()))) . implode('', array_map(function (NBTTagLong $value) {
            return $value->payloadAsBinary();
        }, $this->getPayload()));
    }

    public function getPayloadSize(): int
    {
        return 4 + 8 * count($this->getPayload());
    }

    public function &get(int $index): NBTTagLong
    {
        $payload = $this->getPayload();
        if ($index < 0 || $index >= count($payload)) {
            throw new \OutOfBoundsException('Index out of bounds');
        }

        return $payload[$index];
    }

    public function set(int $index, NBTTagLong $value)
    {
        $payload = $this->getPayload();
        if ($index < 0 || $index >= count($payload)) {
            throw new \OutOfBoundsException('Index out of bounds');
        }

        $payload[$index] = $value;

        $this->setPayload($payload);
    }
}
