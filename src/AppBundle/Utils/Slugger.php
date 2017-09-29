<?php

namespace AppBundle\Utils;

class Slugger
{
	public static function generateSlug(string $string): string
	{
		return preg_replace('/[^a-z0-9_-]/i', '-', trim(mb_strtolower($string)));
	}
}
