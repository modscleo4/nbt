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
