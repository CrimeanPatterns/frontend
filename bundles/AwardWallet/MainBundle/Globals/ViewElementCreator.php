<?php

namespace AwardWallet\MainBundle\Globals;

class ViewElementCreator
{
    public $marks = [
        'A' => 'track.group.airline',
        'H' => 'track.group.hotel',
        'R' => 'track.group.rent',
        'T' => 'track.group.train',
        'O' => 'track.group.other',
        'C' => 'track.group.card',
        'S' => 'track.group.shop',
        'D' => 'track.group.dining',
        'L' => 'track.group.survey',
        'V' => 'track.group.cruise',
        'W' => 'track.group.layover',
    ];
    public $markOrder = 'AHCSRDTVLO';

    private $lastElement;
    private $list = [];
    private $trips = [];
    private $lastTrip;
    private $timelineOption = [];

    public function __construct()
    {
    }

    public function prepareTabs()
    {
        $tabs = [['name' => 'track.group.all', 'tag' => $this->markOrder, 'checked' => true]];

        foreach ($this->marks as $key => $name) {
            if (($pos = strpos($this->markOrder, $key)) !== false) {
                $tabs[$pos + 1] = ['name' => $name, 'tag' => $key];
            }
        }
        ksort($tabs);
        $items = [];

        foreach ($tabs as $prov) {
            $items[$prov['tag']] = [
                'name' => $prov['name'],
                'list' => [],
            ];
        }

        return [$tabs, $items];
    }

    public function userBox($name = '', $pic = '', $langlist = [], $curlang = '')
    {
    }

    public function prepend()
    {
        if (count($this->list) > 0) {
            $this->lastElement['prepend'] = true;
        }
    }

    public function clearPrepend()
    {
        if (isset($this->lastElement['prepend'])) {
            unset($this->lastElement['prepend']);
        }

        return $this;
    }

    public function clear()
    {
        $this->lastElement = null;
        $this->list = [];

        return $this;
    }

    public function addCustomBox($params = [], $listKey = 'Menu', $boxKey = '')
    {
        $list = $params;
        $list[$listKey] = [];

        if (empty($boxKey)) {
            $this->list[] = &$list;
        } else {
            $this->list[$boxKey] = &$list;
        }
        $this->lastElement = ['customList' => &$list[$listKey], 'customize' => &$list, 'disabled' => $listKey];

        return $this->clearPrepend();
    }

    public function addCustomGroup()
    {
        if (!isset($this->lastElement['customList'])) {
            $this->addCustomBox();
        }
        $list = &$this->lastElement['customList'];
        $group = [];
        $list[] = &$group;
        $this->lastElement['customGroup'] = &$group;

        return $this->clearPrepend();
    }

    public function addCustomItem($params = [], $itemKey = '', $addToGroup = false)
    {
        $item = $params;

        if (!isset($this->lastElement['customGroup']) and $addToGroup) {
            $this->addCustomGroup();
        }

        if (isset($this->lastElement['customGroup'])) {
            $list = &$this->lastElement['customGroup'];
        } else {
            if (!isset($this->lastElement['customList'])) {
                $this->addCustomBox();
            }
            $list = &$this->lastElement['customList'];
        }

        if (empty($itemKey)) {
            $list[] = &$item;
        } else {
            $list[$itemKey] = &$item;
        }

        $this->lastElement['customize'] = &$item;
        unset($this->lastElement['disabled']);

        return $this->clearPrepend();
    }

    public function addCustomParam($param)
    {
        if (is_string($param) and func_num_args() > 1) {
            if (isset($this->lastElement['disabled'])) {
                if ($this->lastElement['disabled'] == $param) {
                    return $this;
                }
            }
            $this->lastElement['customize'][$param] = func_get_arg(1);
        } elseif (is_array($param)) {
            foreach ($param as $key => $val) {
                $this->addCustomParam($key, $val);
            }
        }

        return $this;
    }

    public function getCustomList()
    {
        if (isset($this->lastElement['customList'])) {
            return $this->lastElement['customList'];
        }

        return [];
    }

    public function addBoxOld($groupName = '', $caption = '', $class = '', $count = null)
    {
        $list = [
            'caption' => $caption,
            'classes' => $class,
            'counts' => '',
            'Menu' => [],
        ];

        if (isset($count)) {
            $list['counts'] = $count;
        }

        if (empty($groupName)) {
            $this->list[] = &$list;
        } else {
            $this->list[$groupName] = &$list;
        }
        $this->lastElement = ['oldbox' => &$list['Menu']];

        return $this->clearPrepend();
    }

    public function addBox($class = '', $header = '', $img = '', $id = '')
    {
        $list = [
            'class' => $class,
            'id' => $id,
            'heading' => $header,
            'image' => [],
            'buttons' => [],
            'list' => [],
        ];

        if (!empty($img)) {
            $list['image']['src'] = $img;
            $list['image']['class'] = '';
        }
        $this->list[] = &$list;
        $this->lastElement = ['box' => &$list];

        return $this->clearPrepend();
    }

    public function addButtonGroup()
    {
        $btn = [];

        if (!isset($this->lastElement['box'])) {
            $this->addBox();
        }
        $this->lastElement['box']['buttons'][] = &$btn;
        $this->lastElement['bGroup'] = &$btn;

        return $this->clearPrepend();
    }

    public function addButton($text, $href = '#', $icon = '', $class = '', $active = null, $badge = null, $badgeClass = '', $action = "")
    {
        if (!isset($this->lastElement['bGroup'])) {
            $this->addButtonGroup();
        }
        $btn = [
            'class' => $class,
            'href' => $href,
            'action' => $action,
            'text' => $text,
            'icon' => $icon,
            'badge' => [],
        ];

        if (isset($this->lastElement['pull'])) {
            unset($this->lastElement['pull']);
        }

        if ($active !== null && is_bool($active)) {
            $btn['active'] = $active;
        }

        if ($badge !== null) {
            $btn['badge'] = [
                'value' => $badge,
                'class' => $badgeClass,
            ];
        }
        $this->lastElement['button'] = &$btn;

        if (isset($this->lastElement['prepend']) and (count($this->lastElement['bGroup']) > 0)) {
            $this->lastElement['bGroup'][] = &$btn;
        } else {
            $this->lastElement['bGroup'][] = &$btn;
        }

        return $this->clearPrepend();
    }

    public function addButtonOld($text, $indexName = '', $href = '#', $onclick = null, $class = '', $active = null, $badge = null, $badgeClass = '', $action = null, $actionCaption = '')
    {
        if (!isset($this->lastElement['oldbox'])) {
            $this->addBoxOld();
        }
        $btn = [
            'class' => $class,
            'path' => $href,
            'actionPath' => $action,
            'caption' => $text,
            'onclick' => $onclick,
            'targetPath' => null,
            'count' => $badge,
        ];

        if ($active !== null && is_bool($active)) {
            $btn['selected'] = $active;
        }

        if ($action !== null) {
            $btn['actionCaption'] = $actionCaption;
            $btn['targetActionPath'] = null;
        }

        if (!empty($badgeClass)) {
            $btn['caption'] .= " ($badge)";
            $btn['count'] = null;
        }

        //        $this->lastElement['oldbutton']=&$btn;
        if (!empty($indexName) and is_string($indexName)) {
            $this->lastElement['oldbox'][$indexName] = &$btn;
        } else {
            $this->lastElement['oldbox'][] = &$btn;
        }

        return $this->clearPrepend();
    }

    public function addCheckButton($text, $state = '', $icon = '', $class = '', $id = '')
    {
        if (!isset($this->lastElement['bGroup'])) {
            $this->addButtonGroup();
        }
        $btn = [
            'class' => $class,
            'id' => $id,
            'checkbutton' => $state,
            'text' => $text,
            'icon' => $icon,
            'badge' => [],
        ];

        if (isset($this->lastElement['pull'])) {
            unset($this->lastElement['pull']);
        }
        $this->lastElement['button'] = &$btn;
        $this->lastElement['bGroup'][] = &$btn;

        return $this->clearPrepend();
    }

    public function addCheckBox($text, $state = '', $icon = '', $class = '', $id = '')
    {
        if (!isset($this->lastElement['bGroup'])) {
            $this->addButtonGroup();
        }
        $btn = [
            'class' => $class,
            'id' => $id,
            'checkbox' => $state,
            'text' => $text,
            'icon' => $icon,
            'badge' => [],
        ];

        if (isset($this->lastElement['pull'])) {
            unset($this->lastElement['pull']);
        }

        if (isset($this->lastElement['button'])) {
            unset($this->lastElement['button']);
        }
        $this->lastElement['bGroup'][] = $btn;

        return $this->clearPrepend();
    }

    public function addText($text, $state = '', $icon = '', $class = '')
    {
        if (!isset($this->lastElement['bGroup'])) {
            $this->addButtonGroup();
        }
        $btn = [
            'class' => $class,
            'text' => $text,
            'icon' => $icon,
            'badge' => [],
        ];

        if (isset($this->lastElement['pull'])) {
            unset($this->lastElement['pull']);
        }

        if (isset($this->lastElement['button'])) {
            unset($this->lastElement['button']);
        }
        $this->lastElement['bGroup'][] = $btn;

        return $this->clearPrepend();
    }

    public function addTextInput($text, $value = '', $icon = '', $class = '')
    {
        if (!isset($this->lastElement['bGroup'])) {
            $this->addButtonGroup();
        }
        $btn = [
            'class' => $class,
            'input' => $value,
            'text' => $text,
            'icon' => $icon,
            'badge' => [],
        ];

        if (isset($this->lastElement['pull'])) {
            unset($this->lastElement['pull']);
        }
        $this->lastElement['button'] = &$btn;
        $this->lastElement['bGroup'][] = &$btn;

        return $this->clearPrepend();
    }

    public function addPullMenu()
    {
        if (!isset($this->lastElement['button'])) {
            $this->addButton('');
        }
        $pull = [];
        $this->lastElement['button']['pull'] = &$pull;
        $this->lastElement['pull'] = &$pull;

        return $this->clearPrepend();
    }

    public function addPullItem($text, $href = '#', $class = '', $label = null, $icon = null)
    {
        if (!isset($this->lastElement['pull'])) {
            $this->addPullMenu();
        }
        $item = [
            'class' => $class,
            'ancor' => [
                'class' => '',
                'href' => $href,
                'text' => $text,
            ],
            /** @Ignore */
            'label' => [],
            'input' => [],
        ];

        if ($label !== null) {
            $item['label'] = [
                'class' => '',
                'value' => $label,
            ];
        }

        if ($icon !== null) {
            $item['ancor']['icon'] = $icon;
        }
        $this->lastElement['pull'][] = $item;

        return $this->clearPrepend();
    }

    public function addListGroup()
    {
        // $lst=array();
        if (!isset($this->lastElement['box'])) {
            $this->addBox();
        }
        // $this->lastElement['box']['list'][]=& $lst;
        $this->lastElement['lst'] = &$this->lastElement['box']['list']; // $lst;

        return $this->clearPrepend();
    }

    public function addOptionLink($text, $href = '#', $class = '', $label = null, $icon = null, $addlink = null)
    {
        if (!isset($this->lastElement['lst'])) {
            $this->addListGroup();
        }
        $lst = &$this->lastElement['lst'];
        $item = [
            'class' => $class,
            'ancor' => [
                'class' => '',
                'href' => $href,
                'text' => $text,
            ],
            /** @Ignore */
            'label' => [],
            'input' => [],
        ];

        if ($label !== null) {
            $item['label'] = [
                'class' => '',
                'value' => $label,
            ];
        }

        if ($icon !== null) {
            $item['ancor']['icon'] = $icon;
        }

        if ($addlink !== null) {
            $item['addlink'] = $addlink;
        }
        $lst[] = $item;

        return $this->clearPrepend();
    }

    public function addOptionInput($text, $type, $id = "")
    {
        if (!isset($this->lastElement['lst'])) {
            $this->addListGroup();
        }
        $lst = &$this->lastElement['lst'];
        $item = [
            'class' => '',
            'ancor' => [],
            /** @Ignore */
            'label' => [],
            'input' => [
                'type' => $type,
                'id' => $id,
                'text' => $text,
            ],
        ];
        $lst[] = $item;

        return $this->clearPrepend();
    }

    public function getList()
    {
        $list = $this->list;
        $this->list = [];

        return $list;
    }

    public function getButtons()
    {
        $box = reset($this->list);

        if (!empty($box)) {
            return $box['buttons'];
        }

        return [];
    }

    public function getButtonGroup()
    {
        $group = $this->getButtons();

        if (count($group) > 0) {
            return reset($group);
        }

        return [];
    }

    public function getButtonsOld()
    {
        $box = reset($this->list);

        if (!empty($box)) {
            return $box['Menu'];
        }

        return [];
    }

    public function clearTimeline()
    {
        $this->trips = [];
        $this->lastTrip = null;
        $this->timelineOption = [
            'old' => 1,
        ];

        return $this;
    }

    public function advanceTime()
    {
        if (isset($this->timelineOption['old'])) {
            unset($this->timelineOption['old']);
        } elseif (!isset($this->timelineOption['new'])) {
            $this->timelineOption['new'] = 1;
        }
    }

    public function addSection($item)
    {
        if (isset($this->lastTrip['section'])) {
            $lst = &$this->lastTrip['section'];
        } else {
            $lst = &$this->trips;
        }
        $lst[] = $item;

        return $this;
    }

    public function addTrip()
    {
        return $this;
    }

    public function endTrip()
    {
        return $this;
    }

    public function createCheck($date, $humanDate)
    {
        $item = [
            'type' => 'checkPoint',
            'human' => $humanDate,
            'date' => $date,
        ];

        return array_merge($item, $this->timelineOption);
    }

    public function createSection($kind, $title, $time, $attr = [], $id = null, $num = '', $warn = '', $warnIcon = '', $oldTime = '')
    {
        $item = [
            'type' => 'section',
            'time' => $time,
            'kind' => $kind,
            'carrier' => $title,
        ];

        if (!empty($attr)) {
            $item = array_merge($item, $attr);
        }

        if (!empty($id)) {
            $item['sectionID'] = $id;
            $item['confNumber'] = $num;
        }

        if (!empty($warn)) {
            $item[$warnIcon] = $warn;
        }

        if (!empty($oldTime)) {
            $item['oldtime'] = $oldTime;
        }

        return $item;
    }

    public function createTrip()
    {
        $item = [];

        return $item;
    }

    public function addCheck($date, $humanDate)
    {
        $this->addSection($this->createCheck($date, $humanDate));

        return $this;
    }

    public function getTimeline()
    {
    }

    public function getGCMapUrl($points, $size = 111)
    {
        if (intval($size) < 60) {
            $size = 60;
        }
        $url = 'http://www.gcmap.com/map?p=%path%&MS=wls2&MP=rect&MR=240&MX='
               . "{$size}x{$size}" . '&PM=b:ring5:black%2b%25U&PC=%23ff0000&PW=2&RS=outline&RC=%23ff0000&RW=2';

        foreach ($points as $key => &$stop) {
            if (is_array($stop)) {
                $stop = implode('-', $stop);
            } elseif (!is_string($stop) || count($stop) > 3) {
                unset($points[$key]);
            }
        }

        return str_replace('%path%', implode(',', $points), $url);
    }
}
