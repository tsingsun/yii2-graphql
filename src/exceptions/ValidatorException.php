<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/9/15
 * Time: 上午10:51
 */

namespace yii\graphql\exceptions;


use Throwable;
use yii\base\Exception;
use yii\base\Model;

class ValidatorException extends Exception
{
    public $formatErrors;

    /**
     * ValidatorException constructor.
     * @param Model $model
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($model, $code = 0, Throwable $previous = null)
    {
        parent::__construct("model {$model->formName()} validate false", $code, $previous);
        $this->formatModelErrors($model);
    }

    /**
     * @param Model $model
     */
    private function formatModelErrors($model)
    {
        foreach ($model->getErrors() as $field => $fielsErrors) {
            foreach ($fielsErrors as $error) {
                $this->formatErrors[] = ['code' => $field, 'message' => $error];
            }
        }
    }
}