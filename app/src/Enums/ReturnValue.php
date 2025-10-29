<?php

namespace App\Enums;

enum ReturnValue {

    case NOERROR;
    case DURATION_WORKDAYS_EXCEEDED;

    public function getMessage(): string {
        return match ($this) {

            self::NOERROR => 'No error occurred.',
            self::DURATION_WORKDAYS_EXCEEDED => 'Duration in workday exceeded 8 hours.',

            default => 'Unknown error.',
        };
    }

}
