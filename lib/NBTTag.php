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

abstract class NBTTag implements \JsonSerializable
{
    protected NBTTagType $type;

    public final function getType(): NBTTagType
    {
        return $this->type;
    }

    public abstract function jsonSerialize(): mixed;

    public abstract function toSNBT(bool $format = true, int $iteration = 1): string;

    public abstract function toBinary(): string;

    public function __toString(): string
    {
        return $this->toSNBT(NBTParser::$FORMAT);
    }

    public abstract function getByteLength(): int;
}
