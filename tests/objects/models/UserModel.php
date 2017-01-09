<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/28
 * Time: 下午3:51
 */

namespace yiiunit\extensions\graphql\objects\models;


use yii\db\ActiveRecord;

class UserModel extends ActiveRecord
{
    public static function tableName()
    {
        return 'bas_user';
    }

}