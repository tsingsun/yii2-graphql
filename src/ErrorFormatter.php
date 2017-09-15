<?php

namespace yii\graphql;

use GraphQL\Error\Error;
use yii\graphql\exceptions\ValidatorException;

/**
 * Class ErrorFormatter
 * @package yii\graphql
 */
class ErrorFormatter
{
    public static function formatError(Error $e)
    {
        $previous = $e->getPrevious();

        if ($previous && $previous instanceof ValidatorException) {
            return $previous->formatErrors;
        }

        return $e->toSerializableArray();
    }
}
