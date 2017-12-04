<?php

namespace yii\graphql;

use Yii;
use GraphQL\Error\Error;
use GraphQL\FormattedError;
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
        if ($previous) {
            Yii::$app->getErrorHandler()->logException($previous);
            if ($previous instanceof ValidatorException) {
                return $previous->formatErrors;
            }
        } else {
            Yii::error($e->getMessage(), get_class($e));
        }

        return FormattedError::createFromException($e, YII_DEBUG);
    }
}
