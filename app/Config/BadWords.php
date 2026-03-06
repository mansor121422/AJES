<?php

namespace Config;

/**
 * Bad words list for chat censorship (English and Filipino).
 * Matched words are replaced with "****" when users send messages.
 */
class BadWords
{
    /** @var list<string> English profanity / bad words (common, school-appropriate filter) */
    public static array $english = [
        'damn', 'hell', 'crap', 'ass', 'stupid', 'idiot', 'dumb', 'shit', 'bullshit',
        'bastard', 'bitch', 'fuck', 'fucking', 'wtf', 'omg', 'suck', 'sucks',
        'hate', 'kill', 'die', 'damned', 'crap', 'piss', 'pissed', 'screw',
        'sex',
    ];

    /** @var list<string> Filipino profanity / bad words */
    public static array $filipino = [
        'gago', 'gaga', 'bobo', 'tanga', 'inutil', 'punyeta', 'putang', 'puta',
        'pakyu', 'bilat', 'ulol', 'tarantado', 'pakshet', 'bwisit', 'leche', 'yawa',
        'iyot', 'potangina', 'potang',
    ];

    /**
     * Get all bad words (English + Filipino), lowercase, unique.
     */
    public static function all(): array
    {
        $all = array_merge(self::$english, self::$filipino);
        $all = array_map('strtolower', $all);
        return array_values(array_unique($all));
    }
}
