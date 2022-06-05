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
 * @method array<string, NBTNamedTag> getPayload()
 */
class NBTTagCompound extends NBTNamedTag
{
    protected NBTTagType $type = NBTTagType::TAG_Compound;

    public function toSNBT(bool $format = true, int $iteration = 1): string
    {
        $payload = $this->getPayload();
        $content = array_map(function (NBTNamedTag $tag) use ($format, $iteration) {
            return (preg_match('/[ :]/', $tag->getName()) ? '"' . $tag->getName() . '"' : $tag->getName()) . ':' . ($format ? ' ' : '') . $tag->toSNBT($format, $iteration + 1);
        }, $payload);

        if (!$format) {
            return '{' . implode(',', $content) . '}';
        }

        return "{\n" . str_pad('', $iteration * 2, ' ') . implode(",\n" . str_pad('', $iteration * 2, ' '), $content) . "\n" . str_pad('', ($iteration - 1) * 2, ' ') . "}";
    }

    protected function payloadAsBinary(): string
    {
        $payload = $this->getPayload();
        return implode('', array_map(function (NBTNamedTag $tag) {
            return $tag->toBinary();
        }, $payload)) . (new NBTTagEnd())->toBinary();
    }

    public function getPayloadSize(): int
    {
        $payload = $this->getPayload();
        return array_reduce($payload, function (int $carry, NBTNamedTag $tag) {
            return $carry + $tag->getByteLength();
        }, (new NBTTagEnd)->getByteLength());
    }

    public function keys(): array
    {
        $payload = $this->getPayload();
        return array_map(function (NBTNamedTag $tag) {
            return [
                'name' => $tag->getName(),
                'type' => $tag->getType()->asString()
            ];
        }, $payload);
    }

    public function &get(string $name): NBTNamedTag
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Invalid key.');
        }

        $payload = $this->getPayload();

        if (array_key_exists($name, $payload)) {
            $tag = $payload[$name];
            return $tag;
        }

        throw new \Exception("Tag not found: {$name}");
    }

    public function set(string $name, NBTNamedTag $value)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Invalid key.');
        }

        $payload = $this->getPayload();

        if (array_key_exists($name, $payload)) {
            // Tag found, update its value
            $payload[$name] = $value;
            $this->setPayload($payload);
            return;
        }

        // Tag not found, create a new one
        $value->setName($name);
        $payload[$name] = $value;
        $this->setPayload($payload);
    }
}
