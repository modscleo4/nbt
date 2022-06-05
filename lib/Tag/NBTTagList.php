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

use Modscleo4\NBT\Lib\NBTTag;
use Modscleo4\NBT\Lib\NBTNamedTag;
use Modscleo4\NBT\Lib\NBTTagType;

/**
 * @method array getPayload()
 */
class NBTTagList extends NBTNamedTag
{
    protected NBTTagType $type = NBTTagType::TAG_List;

    public function toSNBT(bool $format = true, int $iteration = 1): string
    {
        $content = array_map(function ($tag) use ($format, $iteration) {
            if ($tag instanceof NBTTag) {
                return $tag->toSNBT($format, $iteration + 1);
            }

            return '' . $tag;
        }, $this->getPayload());

        if (!$format) {
            return '[' . implode(',', $content) . ']';
        }

        return "[\n" . str_pad('', $iteration * 2, ' ') . implode(",\n" . str_pad('', $iteration * 2, ' '), $content) . "\n" . str_pad('', ($iteration - 1) * 2, ' ') . "]";
    }

    protected function payloadAsBinary(): string
    {
        /** @var NBTTagType */
        $listType = $this->getAdditionalMetadata()['listType'];

        return pack('C', $listType->value) . strrev(pack('l', sizeof($this->getPayload()))) . implode('', array_map(function (NBTNamedTag $value) {
            return $value->payloadAsBinary();
        }, $this->getPayload()));
    }

    public function getPayloadSize(): int
    {
        return 1 + 4 + array_reduce($this->getPayload(), function ($carry, $item) {
            // List elements don't have neither type nor name
            return $carry + $item->getByteLength() - 1 - 2;
        }, 0);
    }

    public function &get(int $index): NBTNamedTag
    {
        $payload = $this->getPayload();
        if ($index < 0 || $index >= count($payload)) {
            throw new \OutOfBoundsException('Index out of bounds');
        }

        return $payload[$index];
    }

    public function set(int $index, NBTNamedTag $value)
    {
        $payload = $this->getPayload();
        if ($index < 0 || $index >= count($payload)) {
            throw new \OutOfBoundsException('Index out of bounds');
        }

        $payload[$index] = $value;

        $this->setPayload($payload);
    }
}
