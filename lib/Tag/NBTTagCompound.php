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
class NBTTagCompound extends NBTNamedTag
{
    protected NBTTagType $type = NBTTagType::TAG_Compound;

    protected function toSNBT($iteration = 1): string
    {
        return "{\n" . str_pad('', $iteration * 2, ' ') . implode(",\n" . str_pad('', $iteration * 2, ' '), array_map(function ($tag) use ($iteration) {
            return $tag->getName() . ': ' . $tag->toSNBT($iteration + 1);
        }, array_filter($this->getPayload(), function ($tag) {
            return !($tag instanceof NBTTagEnd);
        }))) . "\n" . str_pad('', ($iteration - 1) * 2, ' ') . "}";
    }

    public function getPayloadSize(): int
    {
        return array_reduce($this->getPayload(), function ($carry, $item) {
            return $carry + $item->getByteLength();
        }, 0);
    }
}
