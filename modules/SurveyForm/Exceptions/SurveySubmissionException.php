<?php

namespace Modules\SurveyForm\Exceptions;

use RuntimeException;

/**
 * A survey submission that cannot be accepted.
 */
class SurveySubmissionException extends RuntimeException
{
    /**
     * The survey is not accepting responses.
     */
    public static function notOpen(): self
    {
        return new self(__('This survey is not open for responses.'));
    }

    /**
     * The user already submitted this survey.
     */
    public static function alreadySubmitted(): self
    {
        return new self(__('You have already completed this survey.'));
    }
}
