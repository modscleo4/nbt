<?php

namespace Modscleo4\NBT\Lib;

use Modscleo4\NBT\Lib\Tag\NBTTagByteArray;
use Modscleo4\NBT\Lib\Tag\NBTTagCompound;
use Modscleo4\NBT\Lib\Tag\NBTTagIntArray;
use Modscleo4\NBT\Lib\Tag\NBTTagList;
use Modscleo4\NBT\Lib\Tag\NBTTagLongArray;

class NBTUtils
{
    public static function getWalking(NBTTagCompound|NBTTagList|NBTTagByteArray|NBTTagIntArray|NBTTagLongArray $tag, string $key): ?NBTNamedTag
    {
        $parts = preg_split('/(\[[^\]]*\])|("[^"]*")|\.+/', $key, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        $current = $tag;
        foreach ($parts as $part) {
            $part = trim($part, '"');

            if (NBTParser::$DEBUG) {
                print("Part: {$part}\n");
            }

            if (preg_match('/^\[(\d+)\]$/', $part, $matches)) {
                $index = $matches[1];
                if (!ctype_digit($index)) {
                    throw new \InvalidArgumentException("Invalid index: '{$index}'");
                }

                if (!($current instanceof NBTTagList || $current instanceof NBTTagByteArray || $current instanceof NBTTagIntArray || $current instanceof NBTTagLongArray)) {
                    throw new \InvalidArgumentException("Cannot access index '{$index}' on non-list tag");
                }

                $current = $current->get((int) $index);
            } else {
                if (!($current instanceof NBTTagCompound)) {
                    throw new \InvalidArgumentException("Cannot access key '{$part}' on non-compound tag");
                }

                $current = $current->get($part);
            }
        }

        return $current;
    }

    public static function setWalking(NBTTagCompound|NBTTagList|NBTTagByteArray|NBTTagIntArray|NBTTagLongArray $tag, string $key, NBTNamedTag $value)
    {
        $parts = preg_split('/(\[[^\]]*\])|("[^"]*")|\.+/', $key, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        $last = trim(array_pop($parts), '"');

        $current = $tag;
        foreach ($parts as $part) {
            $part = trim($part, '"');

            if (NBTParser::$DEBUG) {
                print("Part: {$part}\n");
            }

            if (preg_match('/^\[(\d+)\]$/', $part, $matches)) {
                $index = $matches[1];
                if (!ctype_digit($index)) {
                    throw new \InvalidArgumentException("Invalid index: '{$index}'");
                }

                if (!($current instanceof NBTTagList || $current instanceof NBTTagByteArray || $current instanceof NBTTagIntArray || $current instanceof NBTTagLongArray)) {
                    throw new \InvalidArgumentException("Cannot set index '{$index}' on non-list tag");
                }

                $current = $current->get((int) $index);
            } else {
                if (!($current instanceof NBTTagCompound)) {
                    throw new \InvalidArgumentException("Cannot set key '{$part}' on non-compound tag");
                }

                $current = $current->get($part);
            }
        }

        if (preg_match('/^\[(\d+)\]$/', $last, $matches)) {
            $index = $matches[1];
            if (!ctype_digit($index)) {
                throw new \InvalidArgumentException("Invalid index: '{$index}'");
            }

            if (!($current instanceof NBTTagList || $current instanceof NBTTagByteArray || $current instanceof NBTTagIntArray || $current instanceof NBTTagLongArray)) {
                throw new \InvalidArgumentException("Cannot access index '{$index}' on non-list tag");
            }

            $current = $current->set((int) $index, $value);
        } else {
            if (!($current instanceof NBTTagCompound)) {
                throw new \InvalidArgumentException("Cannot access key '{$last}' on non-compound tag");
            }

            $current = $current->set($last, $value);
        }
    }
}
