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

    public function toSNBT(bool $format = true, int $iteration = 1): string
    {
        $content = array_map(function (NBTNamedTag $tag) use ($format, $iteration) {
            return (preg_match('/[ :]/', $tag->getName()) ? '"' . $tag->getName() . '"' : $tag->getName()) . ':' . ($format ? ' ' : '') . $tag->toSNBT($format, $iteration + 1);
        }, array_filter($this->getPayload(), function ($tag) {
            return !($tag instanceof NBTTagEnd);
        }));

        if (!$format) {
            return '{' . implode(',', $content) . '}';
        }

        return "{\n" . str_pad('', $iteration * 2, ' ') . implode(",\n" . str_pad('', $iteration * 2, ' '), $content) . "\n" . str_pad('', ($iteration - 1) * 2, ' ') . "}";
    }

    protected function payloadAsBinary(): string
    {
        return implode('', array_map(function (NBTNamedTag|NBTTagEnd $value) {
            return $value->toBinary();
        }, $this->getPayload()));
    }

    public function getPayloadSize(): int
    {
        return array_reduce($this->getPayload(), function ($carry, $item) {
            return $carry + $item->getByteLength();
        }, 0);
    }

    public function keys(): array
    {
        return array_map(function (NBTNamedTag $tag) {
            return [
                'name' => $tag->getName(),
                'type' => $tag->getType()->asString()
            ];
        }, array_filter($this->getPayload(), function ($tag) {
            return !($tag instanceof NBTTagEnd);
        }));
    }

    public function get(string $name): NBTNamedTag
    {
        if (empty($name)) {
            return $this;
        }

        $payload = $this->getPayload();
        $parts = explode('.', $name);
        $part = array_shift($parts);

        for ($i = 0; $i < count($payload); $i++) {
            /** @var NBTNamedTag */
            $tag = $payload[$i];

            if (!($tag instanceof NBTTagEnd) && $tag->getName() === $part) {
                if (count($parts) >= 1) {
                    if (!($tag instanceof NBTTagCompound)) {
                        throw new \InvalidArgumentException("Cannot get {$name}: [{$tag->getType()}]{$tag->getName()} is not a compound tag.");
                    }

                    return $tag->get(implode('.', $parts));
                }

                return $tag;
            }
        }

        throw new \Exception("Tag not found: $name");
    }

    public function set(string $name, NBTNamedTag $value)
    {
        if (empty($name)) {
            return $this;
        }

        $parts = explode('.', $name);
        $part = array_shift($parts);
        $payload = $this->getPayload();

        for ($i = 0; $i < count($payload); $i++) {
            /** @var NBTNamedTag */
            $tag = $payload[$i];

            if (!($tag instanceof NBTTagEnd) && $tag->getName() === $part) {
                if (count($parts) >= 1) {
                    if (!($tag instanceof NBTTagCompound)) {
                        throw new \InvalidArgumentException("Cannot set {$name}: [{$tag->getType()}]{$tag->getName()} is not a compound tag.");
                    }

                    $tag->set(implode('.', $parts), $value);
                    return;
                }

                $value->setName($part);
                $payload[$i] = $value;
                $this->setPayload($payload);
                return;
            }
        }

        if (count($parts) === 0) {
            $value->setName($part);
            $payload[] = $value;
            $this->setPayload($payload);
        }
    }
}
