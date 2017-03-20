<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014 - 2017
 * @package yii2-widgets
 * @subpackage yii2-widget-datetimepicker
 * @version 1.4.4
 */

namespace kartik\datetime;

use kartik\base\InputWidget;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * DateTimePicker widget is a Yii2 wrapper for the Bootstrap DateTimePicker plugin by smalot. This is a fork of the
 * DatePicker plugin by @eternicode and adds the time functionality.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 * @see http://www.malot.fr/bootstrap-datetimepicker/
 */
class DateTimePicker extends InputWidget
{
    /**
     * Calendar icon markup
     */
    const CALENDAR_ICON = '<i class="glyphicon glyphicon-calendar"></i>';

    /**
     * HTML native text input type
     */
    const TYPE_INPUT = 1;
    /**
     * Bootstrap prepended input group addon type
     */
    const TYPE_COMPONENT_PREPEND = 2;
    /**
     * Bootstrap appended input group addon type
     */
    const TYPE_COMPONENT_APPEND = 3;
    /**
     * Datetimepicker rendered inline
     */
    const TYPE_INLINE = 4;
    /**
     * Datetimepicker rendered as a button
     */
    const TYPE_BUTTON = 5;

    /**
     * @var string the markup type of widget markup must be one of the TYPE constants.
     */
    public $type = self::TYPE_COMPONENT_PREPEND;

    /**
     * @var string The size of the input - 'lg', 'md', 'sm', 'xs'
     */
    public $size;

    /**
     * @var boolean whether to auto default timezone if not set.
     */
    public $autoDefaultTimezone = true;

    /**
     * @var array the HTML attributes for the button that is rendered for [[DateTimePicker::TYPE_BUTTON]].
     * Defaults to `['class'=>'btn btn-default']`. The following special options are recognized:
     *
     * - `label`: _string_, the button label. Defaults to `<i class="glyphicon glyphicon-calendar"></i>`
     */
    public $buttonOptions = [];

    /**
     * @var array the HTML attributes for the input tag.
     */
    public $options = [];

    /**
     * @var string the layout template to display the buttons (applicable only  when [[type]] is one of
     * [[TYPE_COMPONENT_PREPEND]] or [[TYPE_COMPONENT_APPEND]]). The following tokens will be parsed and replaced:
     * - `{picker}`: will be replaced with the date picker button (rendered as a bootstrap input group addon).
     * - `{remove}`: will be replaced with the date clear/remove button (rendered as a bootstrap input group addon).
     * - `{input}`: will be replaced with the HTML input markup that stores the datetime.
     * The [[layout]] property defaults to the following value if not set:
     * - `{picker}{remove}{input}` for TYPE_COMPONENT_PREPEND
     * - `{input}{remove}{picker}` for TYPE_COMPONENT_APPEND
     */
    public $layout;

    /**
     * @var string|array|boolean the calendar/time picker button configuration.
     * - if this is passed as a string, it will be displayed as is (will not be HTML encoded).
     * - if this is set to false, the picker button will not be displayed.
     * - if this is passed as an array (this is the DEFAULT) it will treat this as HTML attributes
     *   for the button (to be displayed as a Bootstrap addon). The following special keys are recognized;
     *   - `icon` - _string_, the bootstrap glyphicon name/suffix. Defaults to 'calendar'.
     *   - `title` - _string_, the title to be displayed on hover. Defaults to 'Select date & time'.
     */
    public $pickerButton = [];

    /**
     * @var string|array|boolean the calendar/time remove button configuration.
     * - if this is passed as a string, it will be displayed as is (will not be HTML encoded).
     * - if this is set to false, the remove button will not be displayed.
     * - if this is passed as an array (this is the DEFAULT) it will treat this as HTML attributes
     *   for the button (to be displayed as a Bootstrap addon). The following special keys are recognized;
     *   - `icon` - _string_, the bootstrap glyphicon name/suffix. Defaults to 'remove'.
     *   - `title` - _string_, the title to be displayed on hover. Defaults to 'Clear field'.
     */
    public $removeButton = [];
    /**
     * @inheritdoc
     */
    public $pluginName = 'datetimepicker';
    /**
     * @var array the HTML options for the DateTimePicker container
     */
    private $_container = [];

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        $this->_msgCat = 'kvdtime';
        parent::init();
        if ($this->type < 1 || $this->type > 5 || !is_int($this->type)) {
            throw new InvalidConfigException(
                "Invalid value for the property 'type'. Must be an integer between 1 and 5."
            );
        }
        if ($this->autoDefaultTimezone && empty($this->pluginOptions['timezone']) && !empty(Yii::$app->timezone)) {
            $this->pluginOptions['timezone'] = Yii::$app->timezone;
        }
        $dir = Yii::getAlias('@vendor/kartik-v/yii2-widget-datetimepicker');
        $this->initI18N($dir);
        $s = DIRECTORY_SEPARATOR;
        $this->setLanguage('bootstrap-datetimepicker.', "{$dir}{$s}assets{$s}");
        $this->parseDateFormat('datetime');
        if (empty($this->_container['id'])) {
            $this->_container['id'] = $this->options['id'] . '-datetime';
        }
        if (empty($this->layout)) {
            if ($this->type == self::TYPE_COMPONENT_PREPEND) {
                $this->layout = '{picker}{remove}{input}';
            }
            if ($this->type == self::TYPE_COMPONENT_APPEND) {
                $this->layout = '{input}{remove}{picker}';
            }
        }
        $this->registerAssets();
        echo $this->renderInput();
    }

    /**
     * Registers the needed assets
     */
    public function registerAssets()
    {
        if ($this->disabled) {
            return;
        }
        $view = $this->getView();
        if (!empty($this->_langFile)) {
            DateTimePickerAsset::register($view)->js[] = $this->_langFile;
        } else {
            DateTimePickerAsset::register($view);
        }
        if ($this->type == self::TYPE_INLINE) {
            $this->pluginOptions['linkField'] = $this->options['id'];
            if (!empty($this->pluginOptions['format'])) {
                $this->pluginOptions['linkFormat'] = $this->pluginOptions['format'];
            }
        }
        if ($this->type == self::TYPE_INPUT) {
            $this->registerPlugin($this->pluginName);
        } else {
            $this->registerPlugin($this->pluginName, 'jQuery("#' . $this->_container['id'] . '")');
        }
    }

    /**
     * Renders the source input for the [[DateTimePicker]] plugin. Graceful fallback to a normal HTML  text input - in
     * case JQuery is not supported by the browser
     */
    protected function renderInput()
    {
        if ($this->type == self::TYPE_INLINE) {
            if (empty($this->options['readonly'])) {
                $this->options['readonly'] = true;
            }
            if (empty($this->options['class'])) {
                $this->options['class'] = 'form-control input-sm text-center';
            }
        } else {
            Html::addCssClass($this->options, 'form-control');
        }
        $input = $this->type == self::TYPE_BUTTON ? 'hiddenInput' : 'textInput';
        return $this->parseMarkup($this->getInput($input));
    }

    /**
     * Returns the addon for prepend or append.
     *
     * @param string|array $options the HTML attributes for the addon (if passed as an array) or the addon markup if
     * passed as a string
     * @param string       $type whether the addon is the picker or remove
     *
     * @return string
     */
    protected function renderAddon(&$options, $type = 'picker')
    {
        if ($options === false) {
            return '';
        }
        if (is_string($options)) {
            return $options;
        }
        Html::addCssClass($options, 'input-group-addon');
        $icon = ($type === 'picker') ? 'calendar' : 'remove';
        $icon = '<span class="glyphicon glyphicon-' . ArrayHelper::remove($options, 'icon', $icon) . '"></span>';
        if (empty($options['title'])) {
            $title = ($type === 'picker') ? Yii::t('kvdtime', 'Select date & time') : Yii::t('kvdtime', 'Clear field');
            if ($title != false) {
                $options['title'] = $title;
            }
        }
        return Html::tag('span', $icon, $options);
    }

    /**
     * Parses the input to render based on markup type
     *
     * @param string $input the input markup
     *
     * @return string
     */
    protected function parseMarkup($input)
    {
        $css = $this->disabled ? ' disabled' : '';
        $size = isset($this->size) ? "input-{$this->size} " : '';
        switch ($this->type) {
            case self::TYPE_INPUT:
                Html::addCssClass($this->options, $size . $css);
                return $input;
            case self::TYPE_COMPONENT_PREPEND:
            case self::TYPE_COMPONENT_APPEND:
                $size = isset($this->size) ? "input-group-{$this->size} " : '';
                Html::addCssClass($this->_container, "input-group {$size}date");
                $out = strtr(
                    $this->layout, [
                                     '{picker}' => $this->renderAddon($this->pickerButton),
                                     '{remove}' => $this->renderAddon($this->removeButton, 'remove'),
                                     '{input}' => $input,
                                 ]
                );
                return Html::tag('div', $out, $this->_container);
            case self::TYPE_BUTTON:
                Html::addCssClass($this->_container, 'date' . $css);
                $label = ArrayHelper::remove($this->buttonOptions, 'label', self::CALENDAR_ICON);
                if (!isset($this->buttonOptions['disabled'])) {
                    $this->buttonOptions['disabled'] = $this->disabled;
                }
                if (empty($this->buttonOptions['class'])) {
                    $this->buttonOptions['class'] = 'btn btn-default';
                }
                $button = Html::button($label, $this->buttonOptions);
                Html::addCssStyle($this->_container, 'display:block');
                return Html::tag('span', "{$input}{$button}", $this->_container);
            case self::TYPE_INLINE:
                Html::addCssClass($this->options, $size . $css);
                return Html::tag('div', '', $this->_container) . $input;
            default:
                return '';
        }
    }
}
