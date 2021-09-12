<?php # $Id: serendipity_event_liquid.php 1528 2006-12-01 08:58:47Z garvinhicking $

if (IN_serendipity !== true) {
    die ("Don't hack!");
}


@serendipity_plugin_api::load_language(dirname(__FILE__));

class serendipity_event_liquid extends serendipity_event
{
    var $title = PLUGIN_EVENT_LIQUID_NAME;

    function introspect(&$propbag)
    {
        global $serendipity;

        $propbag->add('name',          PLUGIN_EVENT_LIQUID_NAME);
        $propbag->add('description',   PLUGIN_EVENT_LIQUID_DESC);
        $propbag->add('stackable',     false);
        $propbag->add('author',        'Malte Paskuda');
        $propbag->add('version',       '0.3.1');
        $propbag->add('requirements',  array(
            'serendipity' => '0.8',
            'smarty'      => '2.6.7',
            'php'         => '4.1.0'
        ));
        $propbag->add('cachable_events', array('frontend_display' => true));
        $propbag->add('event_hooks',   array('frontend_display' => true,
                                            'frontend_comment' => true));
        $propbag->add('groups', array('MARKUP'));

        $this->markup_elements = array(
            array(
              'name'     => 'ENTRY_BODY',
              'element'  => 'body',
            ),
            array(
              'name'     => 'EXTENDED_BODY',
              'element'  => 'extended',
            ),
            array(
              'name'     => 'COMMENT',
              'element'  => 'comment',
            ),
            array(
              'name'     => 'HTML_NUGGET',
              'element'  => 'html_nugget',
            )
        );

        $conf_array = array();
        foreach($this->markup_elements as $element) {
            $conf_array[] = $element['name'];
        }
        $propbag->add('configuration', $conf_array);
    }

    function install() {
        serendipity_plugin_api::hook_event('backend_cache_entries', $this->title);
    }

    function uninstall(&$propbag) {
        serendipity_plugin_api::hook_event('backend_cache_purge', $this->title);
        serendipity_plugin_api::hook_event('backend_cache_entries', $this->title);
    }

    function generate_content(&$title) {
        $title = $this->title;
    }


    function introspect_config_item($name, &$propbag)
    {
        $propbag->add('type',        'boolean');
        $propbag->add('name',        constant($name));
        $propbag->add('description', sprintf(APPLY_MARKUP_TO, constant($name)));
        $propbag->add('default', 'true');
        return true;
    }


    function event_hook($event, &$bag, &$eventData, $addData = null) {
        global $serendipity;

        $hooks = &$bag->get('event_hooks');

        if (isset($hooks[$event])) {
            switch($event) {
                case 'frontend_display':
                    foreach ($this->markup_elements as $temp) {
                            if (serendipity_db_bool($this->get_config($temp['name'], true)) && isset($eventData[$temp['element']]) &&
                                !($eventData['properties']['ep_disable_markup_' . $this->instance] ?? null) &&
                                @!in_array($this->instance, ($serendipity['POST']['properties']['disable_markups'] ?? []))) {
                            $element = $temp['element'];
                            $eventData[$element] = $this->_liquid_markup($eventData[$element]);
                        }
                    }
                    return true;
                    break;

                case 'frontend_comment':
                    if (serendipity_db_bool($this->get_config('COMMENT', true))) {
                        echo '<div class="serendipity_commentDirection serendipity_comment_liquid">' . PLUGIN_EVENT_LIQUID_TRANSFORM . '</div>';
                    }
                    return true;
                    break;

                default:
                    return false;
            }
        } else {
            return false;
        }
    }

    
    function _liquid_markup($text) {
        //enable \ as ascape-character:
        $text = str_replace('\*', chr(1), $text);
        $text = str_replace('\[', chr(2), $text);
        $search = array(//strong: **
                        '/\*\*(.*?)\*\*/',
                        //italic: *
                        '/\*(.*?)\*/',
                        //images
                        '/\[\[([^ ]*?)\]\]/',
                        //link without name:
                        '/\[([^ ]*?)\]/',
                        //link with title: [url "title" name]
                        '/\[([^ ]*?) \&quot;(.*?)\&quot; (.*?)\]/',
                        //if " aren't converted
                        '/\[([^ ]*?) "(.*?)" (.*?)\]/',
                        //link: [url name]
                        '/\[(.*?) (.*?)\]/',
                       );
        $replace = array("<strong>$1</strong>",
                        "<em>$1</em>",
                        "<img src=\"$1\" />",
                        "<a href=\"$1\">$1</a>",
                        "<a href=\"$1\" title=\"$2\">$3</a>",
                        "<a href=\"$1\" title=\"$2\">$3</a>",
                        "<a href=\"$1\">$2</a>",
                        );
        $search_elements = count($search);
        for($i = 0; $i < $search_elements; $i++) {
            $text = preg_replace($search[$i], $replace[$i], $text);
        }
        //reinsert escaped charachters:
        $text = str_replace(chr(1), '*', $text);
        $text = str_replace(chr(2), '[', $text);
        return $text;

    }
}

/* vim: set sts=4 ts=4 expandtab : */
?>
