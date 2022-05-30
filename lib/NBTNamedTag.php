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

abstract class NBTNamedTag extends NBTTag
{
    public function __construct(private $name = '', private $payload = null, private $additionalMetadata = [])
    {
    }

    public final function getName(): string
    {
        return $this->name;
    }

    public final function getPayload(): mixed
    {
        return $this->payload;
    }

    public final function getAdditionalMetadata(): array
    {
        return $this->additionalMetadata;
    }

    public function jsonSerialize(): mixed
    {
        return array_merge($this->getAdditionalMetadata(), [
            'type' => $this->type->asString(),
            'name' => $this->name,
            'payload' => $this->payload
        ]);
    }

    public function getPayloadSize(): int
    {
        return $this->type->size();
    }

    public function getByteLength(): int
    {
        return 1 + 2 + strlen($this->name ?? "") + $this->getPayloadSize();
    }

    protected abstract function payloadAsBinary(): string;

    public function toBinary(): string
    {
        return pack('C', $this->type->value) . pack('n', strlen($this->name ?? "")) . $this->name . $this->payloadAsBinary();
    }
}
