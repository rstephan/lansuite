<?php

$ms_number = 0;

class MasterSearch2 {
    var $query;
    var $result_field = array();
    var $search_fields = array();
    var $search_dropdown = array();
    var $icon_field = array();
    var $multi_select_action = array();
  var $config = array();
  var $bgcolors = array();
  var $bgcolor_attr = '';
  var $sql_select_field_list = array();
  var $sql_select_field_alias_list = array();
  var $post_in_get = '';
  var $NoItemsText = '';

  // Constructor
  function MasterSearch2($module = '') {
    global $language, $lang, $ms_number;

    $ms_number++;

    $this->config['EntriesPerPage'] = 20;
    
    $this->query['from'] = '';
    $this->query['where'] = '';
    $this->query['group_by'] = '';
    $this->query['order_by'] = '';
    $this->query['limit'] = '';

    // Add $_POST[]-Fields to $working_link
    if ($_POST['search_input']) foreach($_POST['search_input'] as $key => $val) $this->post_in_get .= "&search_input[$key]=$val";
    elseif ($_GET['search_input']) foreach($_GET['search_input'] as $key => $val) $this->post_in_get .= "&search_input[$key]=$val";     
    if ($_POST['search_dd_input']) foreach($_POST['search_dd_input'] as $key => $val) {
      if (is_array($val)) foreach($val as $key2 => $val2) $this->post_in_get .= "&search_dd_input[$key][$key2]=$val2"; 
      else $this->post_in_get .= "&search_dd_input[$key]=$val";
    } elseif ($_GET['search_dd_input']) foreach($_GET['search_dd_input'] as $key => $val) {
      if (is_array($val)) foreach($val as $key2 => $val2) $this->post_in_get .= "&search_dd_input[$key][$key2]=$val2";
      else $this->post_in_get .= "&search_dd_input[$key]=$val";
    }

    // Write back from $_GET[] to $_POST[]
    if (!isset($_POST['search_input']) and $_GET['search_input']) foreach($_GET['search_input'] as $key => $val) $_POST['search_input'][$key] = $val;
    if (!isset($_POST['search_dd_input']) and $_GET['search_dd_input']) foreach($_GET['search_dd_input'] as $key => $val) {
      if (is_array($val)) foreach($val as $key2 => $val2) $_POST['search_dd_input'][$key][$key2] = $val2; 
      else $_POST['search_dd_input'][$key] = $val;
    }
  }

  function AddSelect($sql_field){
    if ($sql_field and !in_array($sql_field, $this->sql_select_field_list)) array_push($this->sql_select_field_list, $sql_field);

    // cut of 'table.', in front of field name
    $first_as = strpos(strtolower($sql_field), ' as ');
#    $first_dot = strpos($sql_field, '.');
    if ($first_as > 0) $sql_field = substr($sql_field, $first_as + 4, strlen($sql_field));
#    elseif ($first_dot > 0) $sql_field = substr($sql_field, $first_dot + 1, strlen($sql_field));
    if ($sql_field and !in_array($sql_field, $this->sql_select_field_alias_list)) array_push($this->sql_select_field_alias_list, $sql_field);
  }

  function AddBGColor($sql_field, $color_list){
    $this->AddSelect($sql_field);
    $this->bgcolors = $color_list;
    $this->bgcolor_attr = $sql_field;
  }

  function AddTextSearchField($caption, $sql_fields) {
    $arr = array();
    $arr['caption'] = $caption;
    $arr['sql_fields'] = $sql_fields;
    array_push($this->search_fields, $arr);
  }

  function AddTextSearchDropDown($caption, $sql_field, $selections, $default = '', $multiple = 0) {
    $arr = array();
    $arr['caption'] = $caption;
    $arr['sql_field'] = $sql_field;
    $arr['selections'] = $selections;
    $arr['multiple'] = $multiple;

    $curr_pos = count($this->search_dropdown);
    if ($default != '' and !isset($_POST["search_dd_input"][$curr_pos])) $_POST["search_dd_input"][$curr_pos] = $default;

    array_push($this->search_dropdown, $arr);
  }

  function AddResultField($caption, $sql_field, $callback = '', $max_char = 0, $width = 0) {
    $arr = array();
    $arr['caption'] = $caption;
    $arr['sql_field'] = $sql_field;
    $arr['callback'] = $callback;
    $arr['max_char'] = $max_char;
    $arr['width'] = $width;
    array_push($this->result_field, $arr);

    $this->AddSelect($sql_field); 
  }

  function AddIconField($icon_name, $link = '', $tooltipp = '', $callback = '') {
    $arr = array();
    $arr['icon_name'] = $icon_name;
    $arr['link'] = $link;
    $arr['tooltipp'] = $tooltipp;
    $arr['callback'] = $callback;
    array_push($this->icon_field, $arr);
  }

  function AddMultiSelectAction($caption, $action, $security_question = 0, $icon = '') {
    $arr = array();
    $arr['caption'] = $caption;
    $arr['action'] = $action;
    $arr['security_question'] = $security_question;
    $arr['icon'] = $icon;
    array_push($this->multi_select_action, $arr);
  }

    function PrintSearch($working_link, $select_id_field, $multiaction = '') {
    global $smarty, $db, $config, $dsp, $templ, $func, $auth, $line, $gd, $lang, $ms_number;

    $working_link .= $this->post_in_get;
    $working_link .= '&ms_number='. $ms_number;
    $this->AddSelect($select_id_field); 
    $min_skipped_items = 99;
    
    ###### Generate Where
    if ($this->query['where'] == '') $this->query['where'] = '1 = 1';
    
    // Generate where from input fields
    $z = 0;
    if ($this->search_fields) foreach ($this->search_fields as $current_field_list) {
      if ($_POST["search_input"][$z] != '') {
        $x = 0;
        $sql_one_search_field = '';
        if ($current_field_list['sql_fields']) foreach ($current_field_list['sql_fields'] as $sql_field => $compare_mode) {
          if ($x > 0) $sql_one_search_field .= ' OR ';
          switch ($compare_mode) {
            case 'exact':
              $sql_one_search_field .= "($sql_field = '". $_POST["search_input"][$z] ."')";
            break;
            case 'fulltext':
              $sql_one_search_field .= "(MATCH ($sql_field) AGAINST ('{$_POST["search_input"][$z]}' IN BOOLEAN MODE))";
              $this->AddResultField($lang['ms2']['score'], "ROUND(MATCH ($sql_field) AGAINST ('{$_POST["search_input"][$z]}' IN BOOLEAN MODE), 3) AS score");
            break;
            case '1337':
                    $key_1337 = $_POST["search_input"][$z];
                    $key_1337 = str_replace ('?', '[?]', $key_1337);
                    $key_1337 = str_replace ('+', '[+]', $key_1337);
                    $key_1337 = str_replace ('*', '[*]', $key_1337);
                    $key_1337 = str_replace ('.', '[.]', $key_1337);
                    $key_1337 = str_replace ('|', '[|]', $key_1337);
                    $key_1337 = str_replace ('[', '[[]', $key_1337);

                    $key_1337 = str_replace ("o", "(o|0)", $key_1337);
                    $key_1337 = str_replace ("O", "(O|0)", $key_1337);
                    $key_1337 = str_replace ("l", "(l|1|\\\\||!)", $key_1337);
                    $key_1337 = str_replace ("L", "(L|1|\\\\||!)", $key_1337);
                    $key_1337 = str_replace ("i", "(i|1|\\\\||!)", $key_1337);
                    $key_1337 = str_replace ("I", "(I|1|\\\\||!)", $key_1337);
                    $key_1337 = str_replace ("e", "(e|3|€)", $key_1337);
                    $key_1337 = str_replace ("E", "(E|3|€)", $key_1337);
                    $key_1337 = str_replace ("t", "(t|7)", $key_1337);
                    $key_1337 = str_replace ("T", "(T|7)", $key_1337);
                    $key_1337 = str_replace ("a", "(a|@)", $key_1337);
                    $key_1337 = str_replace ("A", "(A|@)", $key_1337);
                    $key_1337 = str_replace ("s", "(s|5|$)", $key_1337);
                    $key_1337 = str_replace ("S", "(S|5|$)", $key_1337);
                    $key_1337 = str_replace ("z", "(z|2)", $key_1337);
                    $key_1337 = str_replace ("Z", "(Z|2)", $key_1337);

                    $key_1337 = str_replace (']', '[[.right-square-bracket.]]', $key_1337);
              $sql_one_search_field .= "($sql_field REGEXP '$key_1337')";
            break;
            default:
              $sql_one_search_field .= "($sql_field LIKE '%". $_POST["search_input"][$z] ."%')";
            break;
          }
          $x++;
        }
        if ($sql_one_search_field != '') $this->query['where'] .= " AND ($sql_one_search_field)";
      }
      $z++;
    }

    // Generate additional where from dropdown fields 
    $z = 0;
    if ($this->search_dropdown) foreach ($this->search_dropdown as $current_field_list) {
      if ($_POST["search_dd_input"][$z] != '') {
        if ($current_field_list['sql_field'] != '') {
          if (is_array($_POST["search_dd_input"][$z])) $values = $_POST["search_dd_input"][$z];
          else $values = explode(',', $_POST["search_dd_input"][$z]);
          
          $x = 0;
          $sql_one_search_field = '';
          foreach ($values as $value) {
            if ($x > 0) $sql_one_search_field .= ' OR ';

            // Negation, greater than, less than
            $pre_eq = '';
            if (substr($value, 0, 1) == '!' or substr($value, 0, 1) == '<' or substr($value, 0, 1) == '>') {
              $pre_eq = substr($value, 0, 1);
              $value = substr($value, 1, strlen($value) - 1);
            }
            
            if ($value != '') {
              if ($value == 'NULL') $sql_one_search_field .= "({$current_field_list['sql_field']} IS NULL)";
              else $sql_one_search_field .= "({$current_field_list['sql_field']} $pre_eq= '$value')";
            }
            $x++;
          }
          // If COUNT function is used in select, write this variable in the having statement, otherwise in the where statement
          if (strpos($current_field_list['sql_field'], 'OUNT(') == 0) $this->query['where'] .= " AND ($sql_one_search_field)";
          else $this->query['having'] .= "($sql_one_search_field) AND ";
        }
      }
      $z++;
    }

    ###### Modificate Having
    if ($this->query['having'] != '') {
      // Cut off trailing AND, if exists
      if (substr($this->query['having'], strlen($this->query['having']) - 5, 5) == ' AND ')
        $this->query['having'] = substr($this->query['having'], 0, strlen($this->query['having']) - 5);
      // Write HAVING in front of statement
      $this->query['having'] = 'HAVING '.$this->query['having'];
    }

    ###### Generate Select
    $this->query['select'] = implode(', ', $this->sql_select_field_list);
    

    ###### Generate Group By
    $this->query['group_by'] .= $select_id_field;

    ###### Generate Order By
    if (strpos($_GET['order_by'], "\'") > 0) $_GET['order_by'] = ''; # Important for FIND_IN_SET ranking
    if ($_GET['order_by']) {
      if (!in_array($_GET['order_by'], $this->sql_select_field_alias_list)) $func->error(t('Sortieren nach "%1" nicht möglich. Feld ist nicht im Select-Teil definiert', array($_GET['order_by'])), $func->internal_referer);
      else $this->query['order_by'] .= $_GET['order_by'];
      if ($_GET['order_dir']) {
        if ($_GET['order_dir'] != 'ASC' and $_GET['order_dir'] != 'DESC') $func->error(t('Sortieren-Ordnung, darf nur ASC, oder DESC sein'), $func->internal_referer); 
        else $this->query['order_by'] .= ' '. $_GET['order_dir'];
      }

    } elseif ($this->query['default_order_by']) {
      $this->query['order_by'] = $this->query['default_order_by'];
      if ($this->query['default_order_dir']) $this->query['order_by'] .= ' '. $this->query['default_order_dir'];
    }

    if ($this->query['order_by'] == '') $this->query['order_by'] = $select_id_field .' ASC';
    if ($this->query['order_by_end']) $this->query['order_by'] .= ', '. $this->query['order_by_end'];

    ###### Generate Limit
    if ($_GET['ms_page'] == 'all') $this->query['limit'] = '';
    else {
      if ($_GET['ms_page'] != '' and (!$_GET['ms_number'] or $_GET['ms_number'] == $ms_number)) $page_start = (int)$_GET['ms_page'] * (int)$this->config['EntriesPerPage'];
      else $page_start = 0;
      $this->query['limit'] = "LIMIT $page_start, ". $this->config['EntriesPerPage'];
    }
        
    
    ###### Execute SQL
    $res = $db->query("SELECT SQL_CALC_FOUND_ROWS {$this->query['select']}
      FROM {$this->query['from']}
      WHERE {$this->query['where']}
      GROUP BY {$this->query['group_by']}
      {$this->query['having']}
      ORDER BY {$this->query['order_by']}
      {$this->query['limit']}
      ");
/*
    echo "SELECT SQL_CALC_FOUND_ROWS {$this->query['select']}<br>
      FROM {$this->query['from']}<br>
      WHERE {$this->query['where']}<br>
      GROUP BY {$this->query['group_by']}<br>
      {$this->query['having']}<br>
      ORDER BY {$this->query['order_by']}<br>
      {$this->query['limit']}
      ";
*/

    ###### Generate Page-Links
    $count_rows = $db->query_first('SELECT FOUND_ROWS() AS count');
    $count_pages = ceil($count_rows['count'] / $this->config['EntriesPerPage']);
    #if ($_GET['ms_page'] >= $count_pages) $_GET['ms_page'] = $count_pages - 1;

    if ($count_rows['count'] > $this->config['EntriesPerPage']) {
      $link = "$working_link&order_by={$_GET['order_by']}&order_dir={$_GET['order_dir']}&ms_page=";
      $templ['ms2']['pages'] = ("Seiten: ");
      $link_start = ' <a href="';
      $link_end = '" onclick="loadPage(this.href); return false" class="menu">';
      // Previous page link
      if ($_GET['ms_page'] != "all" and (int)$_GET['ms_page'] > 0) {
          $templ['ms2']['pages'] .= $link_start . $link . ($_GET['ms_page'] - 1) . $link_end .'<b>&lt;</b></a>';
      }
      // Direct page link
      $i = 0;
      while($i < $count_pages) {
        if ($_GET['ms_page'] != "all" and $_GET['ms_page'] == $i) $templ['ms2']['pages'] .= (" " . ($i + 1));
        else $templ['ms2']['pages'] .= $link_start . $link . $i . $link_end .'<b>'. ($i + 1) .'</b></a>';
        $i++;
      }
      // Next page link
      if ($_GET['ms_page'] != "all" and ($_GET['ms_page'] + 1) < $count_pages) {
          $templ['ms2']['pages'] .= $link_start . $link . ($_GET['ms_page'] + 1) . $link_end .'<b>&gt;</b></a>';
      }
      // All link
      if ($_GET['ms_page'] == "all") $templ['ms2']['pages'] .= " Alle";
      else $templ['ms2']['pages'] .= ' <a href="' . $link . 'all' . '" class="menu"><b>Alle</b></a>';
    }


    ###### Output Search
    ($_GET['ms_page'] == 'all')? $add_page = '&ms_page=all' : $add_page = '';

    $smarty->assign('action', "$working_link&order_by={$_GET['order_by']}&order_dir={$_GET['order_dir']}");

    // Text Inputs
    $SearchInputs = array();
    $z = 0; $x = 0; $y = 0;
    if ($this->search_fields) foreach ($this->search_fields as $current_field) {
      $arr = array();
      $arr['type'] = 'text';
      $arr['name'] = "search_input[$z]";
      $arr['value'] = $_POST['search_input'][$z];
      $arr['caption'] = $current_field['caption'];      
      if ($current_field['sql_fields']) foreach ($current_field['sql_fields'] as $compare_mode) if ($compare_mode == 'fulltext') {
        $arr['helpletId'] = 'fulltext';
        $arr['helpletText'] = 'Fulltext';
      }
      $SearchInputs[$x][$y] = $arr;
      $y++; $z++;
      if ($y == 2) { $y = 0; $x++; }
    }

    // Dropdown Inputs
    $z = 0;
    if ($this->search_dropdown) foreach ($this->search_dropdown as $current_field) {
      $arr = array();
      $arr['type'] = 'select';
      $arr['name'] = "search_dd_input[$z]";
      $arr['caption'] = $current_field['caption'];
      $arr['options'] = $current_field['selections'];
      $arr['selected'] = $_POST['search_dd_input'][$z];

      $arr['multiple'] = '';
      if ($current_field['multiple']) {
        $arr['multiple'] = ' multiple="multiple" rows="'. $current_field['multiple'] .'"';
        $arr['name'] .= '[]';
      }

      $SearchInputs[$x][$y] = $arr;
      $y++; $z++;
      if ($y == 2) { $y = 0; $x++; }
    }

    // If odd number of input fields, add the last one in a single row
    if ($y == 1) {
      $SearchInputs[$x][$y]['type'] = '';
      $SearchInputs[$x][$y]['caption'] = '&nbsp;';
    }

    if ($this->search_fields or $this->search_dropdown) {
      $smarty->assign('SearchInputs', $SearchInputs);
      $dsp->AddLineTplSmarty($smarty->fetch('modules/mastersearch2/templates/search_case.htm'));
    }

    ###### Output Result
    // When no Items were found
    if ($db->num_rows($res) == 0) {
      if ($this->NoItemsText) $func->Information($this->NoItemsText);
    } else {

      #### Generate Result Head
      $head = array();

      // Checkbox Headline (Empty field)
      if (count($this->multi_select_action) > 0) {
        $head[0]['width'] = '16';
        $head[0]['entry'] = '&nbsp;';
      }

      // Normal headline
      foreach ($this->result_field as $current_field) {

        // Cut out AS
        $first_as = strpos(strtolower($current_field['sql_field']), ' as ');
        if ($first_as > 0) $current_field['sql_field'] = substr($current_field['sql_field'], $first_as + 4, strlen($current_field['sql_field']));

        // Order Link and Image
        ($_GET['ms_page'] == 'all')? $add_page = '&ms_page=all' : $add_page = '';
        ($_GET['order_by'] == $current_field['sql_field'] and $_GET['order_dir'] != 'DESC')? $order_dir = 'DESC' : $order_dir = 'ASC';

        // Generate Headlines
        $arr = array();       
        if ($current_field['caption']) {
          $arr['entry'] = $current_field['caption'];
          $arr['link'] = "$working_link&order_by={$current_field['sql_field']}&order_dir=$order_dir$add_page";

          if ($_GET['order_by'] == $current_field['sql_field']) {
            if ($_GET['order_dir'] == 'DESC') $arr['entry'] .= " <img src=\"design/{$auth['design']}/images/arrows_orderby_desc_active.gif\" border=\"0\" />";
            else $arr['entry'] .= " <img src=\"design/{$auth['design']}/images/arrows_orderby_asc_active.gif\" border=\"0\" />";
          }
        }
        $head[] = $arr;
      }

      #### Generate Result Body
      $body = array();
      $x = 0;
      while($line = $db->fetch_array($res)) { // Start: Row
        $y = 0;

        // cut of 'table.', befor field name
        if (strpos($select_id_field, '.') > 0) $select_id_field = substr($select_id_field, strpos($select_id_field, '.') + 1, strlen($select_id_field));

        // Checkbox
        if (count($this->multi_select_action) > 0) {
          $body[$x][0]['entry'] = '<input type="checkbox" class="checkbox" name="action['. $line[$select_id_field] .']">';
          $y++;
        }

        // Normal fields
        $max_displayed = 0;
        foreach ($this->result_field as $current_field) {    
          $arr = array();

          if ($this->bgcolor_attr) $arr['bgcolor'] = 'style="background-color:'. $this->bgcolors[$line[$this->bgcolor_attr]] .'" ';

          // cut of 'table.', in front of field name
          $first_as = strpos(strtolower($current_field['sql_field']), ' as ');
          $first_dot = strpos($current_field['sql_field'], '.');
          if ($first_as > 0) $current_field['sql_field'] = substr($current_field['sql_field'], $first_as + 4, strlen($current_field['sql_field']));
          elseif ($first_dot > 0) $current_field['sql_field'] = substr($current_field['sql_field'], $first_dot + 1, strlen($current_field['sql_field']));

          // Exec Callback
          if ($current_field['callback']) $arr['entry'] = call_user_func($current_field['callback'], $line[$current_field['sql_field']], $line[$select_id_field]);
          else $arr['entry'] = $line[$current_field['sql_field']];

          // Cut of oversize chars
          if ($current_field['max_char'] and strlen($arr['entry']) > $current_field['max_char'])
            $arr['entry'] = substr($arr['entry'], 0, $current_field['max_char'] - 2) .'...';

          // Link first row to same target as first icon
          if ($y == 0 and !$config['dont_link_first_line'] and $this->icon_field[0]['link']) $arr['link'] = $this->icon_field[0]['link'] . $line[$select_id_field];

          // Width?
          if ($current_field['width']) $arr['width'] = $current_field['width'];

          // Output fro template
          if ($arr['entry'] == '') $arr['entry'] = '&nbsp;';

          $body[$x][$y] = $arr;
          $y++;
        }

        // Icon fields
        $body_icons = array();
        $skipped = 0;
        $first_icon = $y;
        $displayed = 0;
        foreach ($this->icon_field as $current_field) {
          $arr = array();

          if (!$current_field['callback'] or call_user_func($current_field['callback'], $line[$select_id_field])) {
            $arr['type'] = 'icon';
            $arr['width'] = '20px';
            $arr['link'] = $current_field['link'] . $line[$select_id_field];
            $arr['name'] = $current_field['icon_name'];
            $arr['title'] = $current_field['tooltipp'];
            $displayed++;

            $body[$x][$y] = $arr;
            $y++;
          } $skipped++;
        }

        if ($displayed > $max_displayed) $max_displayed = $displayed;
        $x++;
      } // End: Row

      // Move empty Icons to the first possition
      foreach ($body as $k => $v) {
#echo '<br>';
        for ($i = ($max_displayed + $first_icon); $i > $first_icon; $i--) {
#echo $i;
          if ($body[$k][$i]['name'] == '') {
            for ($j = $i; $j > $first_icon; $j--) {
              $prev = $j - 1;

#              echo $body[$k][$prev]['name'] .'->'. $body[$k][$j]['name'] .' | ';
              $body[$k][$j] = $body[$k][$prev];
            }
            unset($body[$k][$first_icon]);
          } #else echo $body[$k][$i]['name'];
        }
      }

      for ($i = 0; $i < $max_displayed; $i++) {
        $arr = array();
        $arr['entry'] = '&nbsp;';
        $arr['width'] = '20px';
        $head[] = $arr;
      }
#print_r($body);
      $smarty->assign('head', $head);
      $smarty->assign('body', $body);

      // Multi-Select Dropdown
      $MultiOptions = array();
      if (count($this->multi_select_action) > 0) {
        $smarty->assign('MultiCaption', t('Bitte auswählen'));
        $z = 0;
        foreach ($this->multi_select_action as $current_action) {
          $arr = array();
          if ($z == 0) $multi_select_actions = '"'. $current_action['action'] .'"';
          else $multi_select_actions .= ', "'. $current_action['action'] .'"';
          if ($z == 0) $security_questions = '"'. $current_action['security_question'] .'"';
          else $security_questions .= ', "'. $current_action['security_question'] .'"';

          $arr['BGIcon'] = $current_action['icon'];
          $arr['caption'] = $current_action['caption'];
          $arr['value'] = $z;
          $MultiOptions[] = $arr;
          $z++;
        }
        $smarty->assign('multi_select_actions', $multi_select_actions);
        $smarty->assign('security_questions', $security_questions);
        $smarty->assign('MultiOptions', $MultiOptions);
      }
      $db->free_result($res);

      $dsp->AddLineTplSmarty($smarty->fetch('modules/mastersearch2/templates/result_case.htm'));
    }
  }
} // End: Class


###### Some global Callbacks
// Callbacks which are only for local interest, should be defined in the modules search-file

function MS2GetDate($time){
  global $dsp, $templ;
  
  if ($time > 0) return '<span class="small">'. date('d.m.y', $time) .'<br />'. date('H:i', $time) .'</span>';
  else {
    $templ['ms2']['icon_name'] = 'no';
    $templ['ms2']['icon_title'] = '-';
    return $dsp->FetchModTpl('mastersearch2', 'result_icon');  
  } 
}

function MS2GetTime($time){
  global $dsp, $templ;
  
  if ($time > 0) return date('H:i', $time);
  else {
    $templ['ms2']['icon_name'] = 'no';
    $templ['ms2']['icon_title'] = '-';
    return $dsp->FetchModTpl('mastersearch2', 'result_icon');  
  } 
}

function TrueFalse($val){
  global $dsp, $templ, $lang;
  
  if ($val) {
    $templ['ms2']['icon_name'] = 'yes';
    $templ['ms2']['icon_title'] = $lang['sys']['yes'];
  } else {
    $templ['ms2']['icon_name'] = 'no';
    $templ['ms2']['icon_title'] = $lang['sys']['no'];
  } 
  return $dsp->FetchModTpl('mastersearch2', 'result_icon');  
}

function UserNameAndIcon($username){
  global $line, $dsp;

  if ($username == '') return '<i>System</i>';
  else if ($line['userid']) return $username .' '. $dsp->FetchUserIcon($line['userid']);
  else return $username;
}

function Text2LSCode($text) {
  global $func;
  
  return $func->text2html($text);
}
?>
