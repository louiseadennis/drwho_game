<?php
    
    // Check that all the probability of the outcomes for this action at this event equal 1
    function transitions_complete_for_modifier_and_action($event_id, $action_id, $story_id, $modifiers, $db) {
        if ($action_id != 100) {
            $sql = "SELECT probability FROM story_transitions WHERE story_id = '{$story_id}' AND event_id = '{$event_id}' AND action_id = '{$action_id}' AND modifiers = '{$modifiers}'";
            if (!$result = $db->query($sql))
                showerror($db);
            $total_probability = 0;
            while ($row=$result->fetch_assoc()) {
                $probability = $row["probability"];
                $total_probability = $total_probability + $probability;
            }
            
            if ($total_probability == 100 || $action_id == 0) {
  
                return 1;
            }
            return 0;
        } else { // special case for travel transitions
            $sql = "SELECT probability, travel_type FROM story_transitions WHERE story_id = '{$story_id}' AND event_id = '{$event_id}' AND action_id = '{$action_id}' AND modifiers = '{$modifiers}'";
             if (!$result = $db->query($sql))
                showerror($db);
            $total_probability = 0;
            $any_travel_type = 0;
            $travel_probs = array();
            while ($row=$result->fetch_assoc()) {
                $probability = $row["probability"];
                $travel_type = $row["travel_type"];
                if ($row["travel_type"] == 'any') {
                    $any_travel_type = 1;
                }
                if (array_key_exists($travel_type, $travel_probs)) {
                    $travel_probs[$travel_type] = $travel_probs[$travel_type] + $probability;
                } else {
                    $travel_probs[$travel_type] = $probability;
                }
            }
            
            if ($any_travel_type == 0) {
                return 0;
            }
  
            foreach ($travel_probs as $total_probability) {
                if ($total_probability != 100) {
                    return 0;
                }
                return 1;
            }
            return 0;

        }
    }
    
    // What is the sum of the probability for this action at this event
    function probability_sum($story_id, $modifiers, $action_id, $event_id, $travel_type, $label, $db) {
        if ($action_id != 100 && $action_id != 0) {
            $sql = "SELECT transition_id from story_transitions where story_id='{$story_id}' and modifiers = '{$modifiers}' and action_id = '{$action_id}' and event_id = '{$event_id}'";
        } else if ($action_id == 0) { // Special case for synchronised actions controlled elsewhere
            $sql = "SELECT transition_id from story_transitions where story_id='{$story_id}' and modifiers = '{$modifiers}' and action_id = '{$action_id}' and event_id = '{$event_id}' and transition_label = '{$label}'";
        } else {
            $sql = "SELECT transition_id from story_transitions where story_id='{$story_id}' and modifiers = '{$modifiers}' and action_id = '{$action_id}' and event_id = '{$event_id}' and travel_type = '{$travel_type}'";
        }
        $other_transitions = sql_return_to_array($sql, "transition_id", $db);
        $total_prob = 0;
         foreach ($other_transitions as $transition) {
            $sql = "SELECT probability from story_transitions where story_id='{$story_id}' and transition_id = '{$transition}'";
            $o_probability = select_sql_column($sql, "probability", $db);
            $total_prob = $total_prob + $o_probability;
        }
        return $total_prob;
    }
    
    //=== Does this event have a transition to handle random travel into it - e.g., by tardis or car or helicopter or whatever the story wasn't expecting?
    function any_travel_type($story_id, $modifiers, $event_id, $db) {
        $sql = "SELECT transition_id from story_transitions where story_id='{$story_id}' and modifiers = '{$modifiers}' and action_id = 100 and event_id = '{$event_id}'";
        $other_transitions = sql_return_to_array($sql, "transition_id", $db);

        // print $sql;
        foreach ($other_transitions as $transition) {
            $sql = "SELECT travel_type from story_transitions where story_id='{$story_id}' and transition_id = '{$transition}'";
            $travel_type = select_sql_column($sql, "travel_type", $db);
             
            if ($travel_type == 'any') {
                return 1;
            }
        }
        return 0;
    }

    //==== What possible outcomes are there for this action at this event
    function probability_outcomes($story_id, $modifiers, $action_id, $event_id, $label, $db) {
        if ($action_id != 0) {
            $sql = "SELECT transition_id from story_transitions where story_id='{$story_id}' and modifiers = '{$modifiers}' and action_id = '{$action_id}' and event_id = '{$event_id}'";
        } else { // Special case for synchronised actions controlled elsewhere
                $sql = "SELECT transition_id from story_transitions where story_id='{$story_id}' and modifiers = '{$modifiers}' and action_id = '{$action_id}' and event_id = '{$event_id}' and transition_label = '{$label}'";
        }
         $other_transitions = sql_return_to_array($sql, "transition_id", $db);
         $outcome_list = "";
         foreach ($other_transitions as $transition) {
             $sql = "SELECT outcome, outcome_text, probability from story_transitions where story_id='{$story_id}' and transition_id = '{$transition}'";
             $outcome = select_sql_column($sql, "outcome", $db);
             $outcome_text = select_sql_column($sql, "outcome_text", $db);
             $o_probability = select_sql_column($sql, "probability", $db);
             $outcome_list = $outcome_list . "<li>$outcome: $outcome_text ($o_probability)</li>";
         }
         return $outcome_list;
     }

    
    
    //================== PRINTING FUNCTIONS
    function print_transition($transition_id, $story_id, $db) {
        $sql = "SELECT * FROM story_transitions WHERE transition_id = '{$transition_id}'";
        
        $label = select_sql_column($sql, "transition_label", $db);
        $outcome = select_sql_column($sql, "outcome", $db);
        $modifiers = select_sql_column($sql, "modifiers", $db);
        $probability = select_sql_column($sql, "probability", $db);
        $outcome_text = select_sql_column($sql, "outcome_text", $db);
        $action_id = select_sql_column($sql, "action_id", $db);
        $event_id = select_sql_column($sql, "event_id", $db);
        $travel_type = select_sql_column($sql, "travel_type", $db);

        $total_prob = probability_sum($story_id, $modifiers, $action_id, $event_id, $travel_type, $label, $db);
         
        $sql = "SELECT text FROM story_events where story_id = '{$story_id}' AND story_number_id = '{$outcome}'";
        $text = select_sql_column($sql, "text", $db);

        $font_color = "black";
        if ($total_prob != 100 && $action_id != 0) {
             print $total_prob;
             $font_color = "red";
        }
        
        if ($action_id == 100 && $travel_type != 'any') {
            $any_travel_type = any_travel_type($story_id, $modifiers, $event_id, $db);
            if (!$any_travel_type) {
                $font_color = "red";
            }
        }


        if ($action_id == 100) {
            print "<p style=\"color:$font_color\">[$modifiers AND $travel_type] -$label ($probability) -> $outcome ($text) : $outcome_text</p>";
        } else {
            print "<p style=\"color:$font_color\">[$modifiers] -$label ($probability) -> $outcome ($text) : $outcome_text</p>";
        }
        
        print "<form method=\"POST\" action=\"edit_transition.php\">";
        print "<input type=\"hidden\" name=\"transition_id\" value=\"$transition_id\">";
        print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
        print "<input type=\"hidden\" name=\"task\" value=\"none\">";
        print "<input type=\"submit\" value=\"Edit $transition_id ($text)\">";
        
        print "</form>";
        
        print "<form method=\"POST\" action=\"edit_event.php\">";
        print "<input type=\"hidden\" name=\"transition_id\" value=\"$transition_id\">";
        print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
        print "<input type=\"hidden\" name=\"task\" value=\"del_transition\">";
        print "<input type=\"hidden\" name=\"story_number_id\" value=\"$event_id\">";
        print "<input type=\"submit\" value=\"Delete Transition\">";
         
        print "</form>";

    }
    
    function print_transitions_for_action($action_id, $story_id, $story_number_id, $event_location, $db) {
        $sql = "SELECT transition_id FROM story_transitions WHERE story_id = '{$story_id}' AND event_id = '{$story_number_id}' AND action_id = '{$action_id}'";
        //print($sql);
        $no_transitions = true;
        
        if (!$result = $db->query($sql))
            showerror($db);
        while ($row=$result->fetch_assoc()) {
            $transition_id = $row["transition_id"];
            print_transition($transition_id, $story_id, $db);
            $no_transitions = false;
        }
        
        print "<form method=\"POST\" action=\"edit_transition.php\">";
        print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
        print "<input type=\"hidden\" name=\"task\" value=\"new_transition\">";
        print "<input type=\"hidden\" name=\"action_id\" value=\"$action_id\">";
        print "<input type=\"hidden\" name=\"event_id\" value=\"$story_number_id\">";
        print "<input type=\"hidden\" name=\"location_id\" value=\"$event_location\">";
        print "<input type=\"submit\" value=\"Add Transition\">";
        print "</form>";
        
        if ($no_transitions) {
            print "<form method=\"POST\" action=\"edit_event.php\">";
            print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
            print "<input type=\"hidden\" name=\"task\" value=\"duplicate_transition\">";
            print "<input type=\"hidden\" name=\"action_id\" value=\"$action_id\">";
            print "<input type=\"hidden\" name=\"story_number_id\" value=\"$story_number_id\">";
            print "<input type=\"hidden\" name=\"location_id\" value=\"$event_location\">";
            $sql = "SELECT transition_id from story_transitions WHERE story_id = '{$story_id}' AND event_id = '{$story_number_id}'";
            if (!$result = $db->query($sql)) {
                showerror($db);
            }
            print "<select name=\"copy_trans\">";
            while ($row=$result->fetch_assoc()) {
                $name = $row["transition_id"];
                print "<option value=\"$name\">$name</option>";
            }
            print "</select>";
            print "<input type=\"submit\" value=\"Duplicate Transition\">";
            print "</form>";
 
            
            print "<form method=\"POST\" action=\"edit_event.php\">";
            print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
            print "<input type=\"hidden\" name=\"task\" value=\"new_default_transition\">";
            print "<input type=\"hidden\" name=\"action_id\" value=\"$action_id\">";
            print "<input type=\"hidden\" name=\"story_number_id\" value=\"$story_number_id\">";
            print "<input type=\"hidden\" name=\"location_id\" value=\"$event_location\">";
            print "<input type=\"submit\" value=\"Add Default Transition\">";
            print "</form>";
        }
        
        print "<hr>";

    }
    
    function print_action_header($title, $handled) {
        print("<li>");
        if (!$handled) {
            print($title);
        } else {
            print("<font color=red>");
            print($title);
            print("</font>");
        }
        
        print(":<br>");
    }
    
    
?>
