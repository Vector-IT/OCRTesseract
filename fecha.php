<?php

function validateDate($dateString, $formats, $locale = 'pt_BR') {
	$generator = new IntlDatePatternGenerator($locale);
	$formatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE);

	foreach ($formats as $format) {
		$pattern = $generator->getBestPattern($format);

		$formatter->setPattern($pattern);

		if ($formatter->parse($dateString)) {
			if ($formatter->format($formatter->parse($dateString)) == $dateString) {
				return true;
			}
        }
	}
	return false;
}

if (validateDate('FEV/2024', ['L/y', 'M/y', 'm/y'], 'pt_BR')) {
	echo 'valid';
} else {
	echo 'invalid';
}