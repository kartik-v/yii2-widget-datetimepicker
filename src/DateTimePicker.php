<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014 - 2018
 * @package yii2-widgets
 * @subpackage yii2-widget-datetimepicker
 * @version 1.4.8
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
     * @var string the markup for the calendar picker icon. If not set this will default to:
     * - `<i class="glyphicon glyphicon-calendar kv-dp-icon"></i>` if [[bsVersion]] is set to `3.x`
     * - `<i class="fas fa-calendar-alt kv-dp-icon"></i>` if [[bsVersion]] is set to `4.x`
     */
    public $pickerIcon;

    /**
     * @var string the markup for the remove icon (which will clear the datepicker data). If not set this will default to:
     * - `<i class="glyphicon glyphicon-remove kv-dp-icon"></i>` if [[bsVersion]] is set to `3.x`
     * - `<i class="fas fa-times kv-dp-icon"></i>` if [[bsVersion]] is set to `4.x`
     */
    public $removeIcon;

    /**
     * @var boolean whether to auto default timezone if not set.
     */
    public $autoDefaultTimezone = true;

    /**
     * @var array the HTML attributes for the button that is rendered for [[TYPE_BUTTON]]. Defaults to
     * - `['class'=>'btn btn-default']` for [[bsVersion]] = '3.x', and
     * - `['class'=>'btn btn-secondary']` for [[bsVersion]] = '4.x'
     * The following special options are recognized:
     * - 'label': string the button label. Defaults to the [[pickerIcon]] setting.
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
     * @var array|string|boolean the calendar picker button configuration.
     * - if this is passed as a _string_, it will be displayed as is (will not be HTML encoded).
     * - if this is set to `false`, the picker button will not be displayed.
     * - if this is passed as an _array_ (which is the default) it will be parsed as HTML attributes for the button (to
     *   be displayed as a Bootstrap addon). The following special keys are recognized;
     *   - `label`: _string_, the label for the button. Defaults to [[pickerIcon]].
     *   - `title`: _string|_boolean_, the title to be displayed on hover. Defaults to 'Select date & time'. To disable,
     *     set it to `false`.
     *   - `position`: _string_, either `append` or `prepend` for (bootstrap 4 input groups). If not set this will
     *     default to the position determined by [[type]] i.e. [[TYPE_COMPONENT_PREPEND]] or [[TYPE_COMPONENT_APPEND]].
     */
    public $pickerButton = [];

    /**
     * @var array|string|boolean the calendar remove button configuration - applicable only for type set to
     * [[TYPE_COMPONENT_PREPEND]] or [[TYPE_COMPONENT_APPEND]].
     * - if this is passed as a _string_, it will be displayed as is (will not be HTML encoded).
     * - if this is set to `false`, the remove button will not be displayed.
     * - if this is passed as an _array_ (this is the DEFAULT) it will treat this as HTML attributes for the button (to
     *   be displayed as a Bootstrap addon). The following special keys are recognized;
     *   - `label`: _string_, the label for the button. Defaults to [[removeIcon]].
     *   - `title`: _string|_boolean_, the title to be displayed on hover. Defaults to 'Clear field'. To disable,
     *     set it to `false`.
     *   - `position`: _string_, either `append` or `prepend` for (bootstrap 4 input groups). If not set this will
     *     default to the position determined by [[type]] i.e. [[TYPE_COMPONENT_PREPEND]] or [[TYPE_COMPONENT_APPEND]].
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
     * @throws \ReflectionException
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

        if ($this->autoDefaultTimezone && empty($this->pluginOptions['timezone']) && !empty(Yii::$app->getTimeZone())) {
            $this->pluginOptions['timezone'] = Yii::$app->getTimeZone();
        }
        $isBs4 = $this->isBs4();
        if ($this->type == self::TYPE_INLINE) {
            $this->pluginOptions['linkField'] = $this->options['id'];
            if (!empty($this->pluginOptions['format'])) {
                $this->pluginOptions['linkFormat'] = $this->pluginOptions['format'];
            }
        }
        $this->pluginOptions = array_replace_recursive([
            'icontype' => $isBs4 ? 'fas' : 'glyphicon',
            'fontAwesome' => $isBs4,
            'icons' => [
                'leftArrow' => $isBs4 ? 'fa-arrow-left' : 'glyphicon-arrow-left',
                'rightArrow' => $isBs4 ? 'fa-arrow-right' : 'glyphicon-arrow-right',
            ],
        ], $this->pluginOptions);
        $this->renderDateTimePicker();
    }

    /**
     * Renders the date time picker widget.
     * @throws InvalidConfigException
     * @throws \ReflectionException
     */
    protected function renderDateTimePicker()
    {
        $this->initIcon('picker', 'calendar', 'calendar-alt');
        $this->initIcon('remove', 'remove', 'times');
        $s = DIRECTORY_SEPARATOR;
        $this->initI18N(__DIR__);
        $this->setLanguage('bootstrap-datetimepicker.', __DIR__ . "{$s}assets{$s}", null, '.js');
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
            DateTimePickerAsset::registerBundle($view, $this->bsVersion)->js[] = $this->_langFile;
        } else {
            DateTimePickerAsset::registerBundle($view, $this->bsVersion);
        }
        if ($this->type == self::TYPE_INPUT) {
            $this->registerPlugin($this->pluginName);
        } else {
            $this->registerPlugin($this->pluginName, 'jQuery("#' . $this->_container['id'] . '")');
        }
    }

    /**
     * Initializes picker icon and remove icon
     * @param string $type the icon type 'picker' or 'remove'
     * @param string $bs3Icon the icon suffix name for Bootstrap 3 version
     * @param string $bs4Icon the icon suffix name for Bootstrap 4 version
     * @throws InvalidConfigException
     */
    protected function initIcon($type, $bs3Icon, $bs4Icon)
    {
        $css = $this->isBs4() ? "fas fa-{$bs4Icon}" : "glyphicon glyphicon-{$bs3Icon}";
        $icon = $type . 'Icon';
        if (!isset($this->$icon)) {
            $this->$icon = Html::tag('i', '', ['class' => $css . ' kv-dp-icon']);
        }
    }

    /**
     * Renders the source input for the [[DateTimePicker]] plugin. Graceful fallback to a normal HTML  text input - in
     * case JQuery is not supported by the browser
     * @throws InvalidConfigException
     */
    protected function renderInput()
    {
        if ($this->type == self::TYPE_INLINE) {
            if (!isset($this->options['readonly'])) {
                $this->options['readonly'] = true;
            }
            if (!isset($this->options['class'])) {
                $this->options['class'] = 'form-control text-center';
            }
        } else {
            Html::addCssClass($this->options, 'form-control');
        }
        $input = $this->type == self::TYPE_BUTTON ? 'hiddenInput' : 'textInput';
        return $this->parseMarkup($this->getInput($input));
    }

    /**
     * Returns the addon to render
     *
     * @param array $options the HTML attributes for the addon
     * @param string $type whether the addon is the picker or remove
     *
     * @return string
     * @throws InvalidConfigException
     */
    protected function renderAddon(&$options, $type = 'picker')
    {
        $isPicker = $type === 'picker';
        if ($options === false || (!$isPicker && $type !== 'remove')) {
            return '';
        }
        if (is_string($options)) {
            return $options;
        }
        $css = $this->isBs4() ? 'input-group-text' : 'input-group-addon';
        Html::addCssClass($options, [$css, "kv-datetime-{$type}"]);
        $iconType = "{$type}Icon";
        $icon = ArrayHelper::remove($options, 'label', $this->$iconType);
        $title = ArrayHelper::getValue($options, 'title', '');
        if ($title !== false && empty($title)) {
            $options['title'] = $isPicker ? Yii::t('kvdtime', 'Select date & time') : Yii::t('kvdtime', 'Clear field');
        }
        return Html::tag('span', $icon, $options);
    }

    /**
     * Parses the input to render based on markup type
     *
     * @param string $input the input markup
     *
     * @return string
     * @throws InvalidConfigException
     */
    protected function parseMarkup($input)
    {
        $disabled = $this->disabled ? 'disabled' : '';
        $size = isset($this->size) ? "input-{$this->size} " : '';
        $isBs4 = $this->isBs4();
        switch ($this->type) {
            case self::TYPE_INPUT:
            case self::TYPE_INLINE:
                Html::addCssClass($this->options, [$size, $disabled]);
                return $this->type === self::TYPE_INPUT ? $input : Html::tag('div', $input, $this->_container);
            case self::TYPE_COMPONENT_PREPEND:
            case self::TYPE_COMPONENT_APPEND:
                $size = isset($this->size) ? "input-group-{$this->size}" : '';
                Html::addCssClass($this->_container, ['input-group', $size, 'date']);
                $position = $this->type === self::TYPE_COMPONENT_APPEND ? 'append' : 'prepend';
                $pickerPos = ArrayHelper::remove($this->pickerButton, 'position', $position);
                $removePos = ArrayHelper::remove($this->removeButton, 'position', $position);
                $picker = $this->renderAddon($this->pickerButton);
                $remove = $this->renderAddon($this->removeButton, 'remove');
                if ($isBs4) {
                    $picker = Html::tag('div', $picker, ['class' => 'input-group-' . $pickerPos]);
                    $remove = Html::tag('div', $remove, ['class' => 'input-group-' . $removePos]);
                }
                $out = strtr($this->layout, [
                    '{picker}' => $picker,
                    '{remove}' => $remove,
                    '{input}' => $input,
                ]);
                return Html::tag('div', $out, $this->_container);
            case self::TYPE_BUTTON:
                Html::addCssClass($this->_container, ['date', $disabled]);
                $label = ArrayHelper::remove($this->buttonOptions, 'label', $this->pickerIcon);
                if (!isset($this->buttonOptions['disabled'])) {
                    $this->buttonOptions['disabled'] = $this->disabled;
                }
                if (empty($this->buttonOptions['class'])) {
                    $this->buttonOptions['class'] = 'btn btn-' . ($isBs4 ? 'secondary' : 'default');
                }
                $button = Html::button($label, $this->buttonOptions);
                Html::addCssStyle($this->_container, 'display:block');
                return Html::tag('span', "{$input}{$button}", $this->_container);
            default:
                return '';
        }
    }
}
