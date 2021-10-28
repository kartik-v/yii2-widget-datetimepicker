<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014 - 2021
 * @package yii2-widgets
 * @subpackage yii2-widget-datetimepicker
 * @version 1.5.0
 */

namespace kartik\datetime;

use kartik\base\AssetBundle;

/**
 * Asset bundle for [[DateTimePicker]] widget
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class DateTimePickerAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setSourcePath(__DIR__.'/assets');
        $bsCss = 'bootstrap-datetimepicker'.($this->isBs(3) ? '3' : '4');
        $this->setupAssets('css', ['css/'.$bsCss, 'css/datetimepicker-kv']);
        $this->setupAssets('js', ['js/bootstrap-datetimepicker']);
        parent::init();
    }
}