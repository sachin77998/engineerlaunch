<?php
namespace App\Support;
use Giggsey\Locale\Locale;
class Countries{public static function getNames(string $locale='en'):array{return Locale::getAllCountriesForLocale(strtolower($locale));}}
