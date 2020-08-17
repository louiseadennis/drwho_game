<?php
    
    class story_automaton
    {
        public $states = array();
        public $transition_function = array();
        public $events = array();
        
        function __construct($story_id, $db) {
            // These are all the events where someone is present
            $locations = get_story_locations($story_id, $db);
            
            $event_sets = array();
            foreach ($locations as $location) {
                $event1 = get_initial_event($story_id, $location, $db);
                $event2 = get_not_present_initial_event($story_id, $location, $db);
                // print("Events: $event1, $event2 <br>");
                array_push($this->events, $event1, $event2);
                if (count($event_sets) == 0) {
                    $event_array1 = array($event1);
                    $event_array2 = array($event2);
                    array_push($event_sets, $event_array1);
                    array_push($event_sets, $event_array2);
                } else {
                    $new_event_sets = array();
                    foreach ($event_sets as $event_set) {
                        $event_set2 = $event_set;
                        array_push($event_set, $event1);
                        array_push($event_set2, $event2);
                        array_push($new_event_sets, $event_set, $event_set2);
                    }
                    $event_sets = $new_event_sets;
                }
            }
            
            // This should get rid of the event set where not characters are present anywhere.
            array_pop($event_sets);
            
            foreach($event_sets as $events) {
                $state = new story_state($events, $story_id, $db, $this);
                array_push($this->states, $state);
            }
                        

            
            // $initial_state = new story_state($initial_events, $story_id, $db, $this);
            // print $initial_state->story_state_string();
            // array_push($this->states, $initial_state);
            
            $this->construct_automaton($this->states[0], $db);
        }
        
        function construct_automaton($state, $db) {
            $state->exploring();
            $state_string = $state->story_state_string();
            // print "Finding next states for $state_string<br>";
            
            foreach ($state->events as $event) {
                $event_state_string = $event->location_event_state_string($db);
                // print "Finding next states for $event_state_string<br>";
                while (!$state->fully_explored($event)) {
                    $next_transition = $state->next_unexplored_transition($event); // don't forget to remove unexplored transition from state
                    // Null transitions are no change
                    if ($next_transition != "null") {
                        $transition_string = $next_transition;
                        // print("Next transition: $transition_string <br>");
                        $new_state = $state->next_state($next_transition, $db);
                        // print("Got new state<br>");
                        $new_state_string = $new_state->story_state_string();
                        // print "New state is $new_state_string<br>";
                        
                        if (!$this->state_member($new_state)) {
                            // print ("Pushing $new_state_string onto states<br>");
                            array_push($this->states, $new_state);
                            array_push($this->transition_function, new transition($state, $next_transition, $new_state));
                        } else {
                            array_push($this->transition_function, new transition($state, $next_transition, $this->get_state_matching($new_state)));
                        }
                    }
                }
            }
            $state->explored();
            
            if ($this->unexplored_state()) {
                $this->construct_automaton($this->get_next_state(), $db);
            }
        }
        
        function print_automaton() {
            foreach ($this->transition_function as $transition) {
                $transition->print_transition();
            }
        }
        
        function state_member($new_state) {
            foreach ($this->states as $state) {
                if ($state->is_equal($new_state)) {
                    return 1;
                }
            }
            return 0;
        }
        
        function get_state_matching($new_state) {
            foreach ($this->states as $state) {
                if ($state->is_equal($new_state)) {
                    return $state;
                }
            }
            return 0;
        }
        
        function unexplored_state() {
            foreach ($this->states as $state) {
                if ($state->under_exploration == 0) {
                    return 1;
                }
            }
            return 0;
        }
        
        function get_next_state() {
            foreach ($this->states as $state) {
                if ($state->under_exploration == 0) {
                    // print $state->story_state_string();
                    return $state;
                }
            }
        }
        
        function get_event($event_id) {
            foreach ($this->events as $event) {
                if ($event->event_id == $event_id) {
                    return $event;
                }
            }
        }
        
        
    }
    
    class story_state
    {
        public $events = array();
        public $unexplored_transitions = array();
        public $under_exploration = 0;
        public $story_id;
        public $automaton;
        
        function __construct()
        {
            $a = func_get_args();
            $i = func_num_args();
            if (method_exists($this,$f='__construct'.$i)) {
                call_user_func_array(array($this,$f),$a);
            }
        }
        
        function __construct4($event_list, $story_id, $db, $automaton) {
            //print("A");
            foreach ($event_list as $event) {
                $location_event = new location_event_state($event, $story_id, $db);
                array_push($this->events, $location_event);
                $event_string = $location_event->location_event_state_string($db);
                // print ("Location Event: $event_string<br>");
                $this->unexplored_transitions[$event] = $location_event->transition_labels;
                $this->story_id = $story_id;
                $this->automaton = $automaton;
                if (!in_array($location_event, $automaton->events)) {
                     array_push($automaton->events, $location_event);
                }
                $string = $this->story_state_string();
                //print ("Created 4: $string<br>");
                //foreach ($this->unexplored_transitions[$event] as $transition) {
                //    print($transition->transition_string());
                //    print("<br>");
                //}
            }
        }
        
        function __construct3($event_list, $story_id, $automaton) {
            // print("Hey!<br>");
            $this->automaton = $automaton;
            $this->story_id = $story_id;
            foreach ($event_list as $event) {
                // print($event->event_id);
                array_push($this->events, $event);
                $this->unexplored_transitions[$event->event_id] = $event->transition_labels;
                if (!in_array($event, $automaton->events)) {
                    array_push($automaton->events, $event);
                }
            }
            $string = $this->story_state_string();
            //print ("Created 2: $string");
        }
        
        function is_equal($state) {
            foreach ($this->events as $event) {
                $accounted = 0;
                foreach ($state->events as $new_event) {
                    if ($event->event_id == $new_event->event_id) {
                        $accounted = 1;
                        break;
                    }
                }
                
                if ($accounted == 0) {
                    return 0;
                }
            }
            
            return 1;
        }

        function fully_explored($event) {
            $event_id = $event->event_id;
            // print("hey $event_id<br>");
            if (count($this->unexplored_transitions[$event_id]) == 0) {
                return 1;
            } else {
                //print("not fully explored<br>");
                return 0;
            }
        }
        
        function next_unexplored_transition($event) {
            $transition = array_pop($this->unexplored_transitions[$event->event_id]);
            
            return $transition;
        }
        
        function exploring() {
            $this->under_exploration = 1;
        }
        
        function explored() {
            $this->under_exploration = 2;
        }
        
        function next_state($transition, $db) {
            $event_list = array();
            foreach ($this->events as $event) {
                $next_event = $event->next_event($transition);
                if (!$event->unhandled($transition)) {
                    array_push($event_list, new location_event_state($next_event, $this->story_id, $db));
                    
                    // $boolean = in_array($transition, $this->unexplored_transitions[$event->event_id]);
                    if (in_array($transition, $this->unexplored_transitions[$event->event_id])) {
                        //print("whee!<br>");
                        $this->unexplored_transitions[$event->event_id] = array_diff($this->unexplored_transitions[$event->event_id], [$transition]);
                    }
                    // print("Pushed $next_event onto event_list<br>");
                } else {
                    array_push($event_list, new location_event_state(0, $this->story_id, $db));
                    // print("Pushed 0 onto event list<br>");
                }
            }
            // $this->automaton->print_automaton();
            return new story_state($event_list, $this->story_id, $this->automaton);
        }
        
        function story_state_string() {
            $out_string = "<";
            foreach ($this->events as $event) {
                $out_string .= "$event->event_id,";
            }
            $out_string .= ">";
            return $out_string;
        }
        
    }
    
    class location_event_state
    {
        public $event_id;
        public $exploring = 0;
        public $explored = 0;
        public $transitions = array();
        public $transition_labels = array();
        public $story_id;
        public $unhandled_transitions = array();
        public $empathy_unhandled = 1;
        public $tech_unhandled = 1;
        public $running_unhandled = 1;
        public $combat_unhandled = 1;
        public $willpower_unhandled = 1;
        public $observation_unhandled = 1;
        public $other_transition_issue = 0;
        public $end_state = 0;
        
        function __construct($event, $story_id, $db) {
            $this->event_id = $event;
            $this->story_id = $story_id;
            $sql = "SELECT success_fail FROM story_events where story_id = '{$story_id}' AND story_number_id='{$event}'";
            $success_fail = select_sql_column($sql, "success_fail", $db);
            //print ($success_fail);
            if ($success_fail != 1) {
                $this->end_state = 1;
            }
            
            // print ($event);
            if ($event != 0 and !$this->end_state) {
                $sql = "SELECT transition_label, outcome, action_id, modifiers FROM story_transitions WHERE event_id = '{$this->event_id}' and story_id = '{$story_id}'";
                //print $sql;
                // print "<br>";
                if (!$result = $db->query($sql))
                    showerror($db);
                //$label_list = array();
                $empathy = 0;
                $tech = 0;
                $running = 0;
                $combat = 0;
                $willpower = 0;
                $observation = 0;
                while ($row=$result->fetch_assoc()) {
                    //print("A");
                    if (!in_array($row["transition_label"], $this->transition_labels)) {
                        array_push($this->transitions, new local_transition($this->event_id, $row["transition_label"], $row["outcome"]));
                        array_push($this->transition_labels, $row["transition_label"]);
                        //array_push($label_list, $row["transition_label"]);
                    }
                    
                    if ($row["action_id"] == 1) {
                        $empathy = 1;
                    } elseif ($row["action_id"] == 2) {
                        $tech = 1;
                    } elseif ($row["action_id"] == 3) {
                        $running = 1;
                    } elseif ($row["action_id"] == 4) {
                        $combat = 1;
                    } elseif ($row["action_id"] == 5) {
                        $willpower = 1;
                    } elseif ($row["action_id"] == 6) {
                        $observation = 1;
                    }
                    
                    
                    //print ($row["modifiers"]);
                    //print("<br>");
                    if (!transitions_complete_for_modifier_and_action($this->event_id, $row["action_id"], $story_id, $row["modifiers"], $db)) {
                        $this->other_transition_issue = 1;
                    }
                }
                
                if ($empathy) {
                    $this->empathy_unhandled = 0;
                }
                if ($tech) {
                    $this->tech_unhandled = 0;
                }
                if ($running) {
                    $this->running_unhandled = 0;
                }
                if ($combat) {
                    $this->combat_unhandled = 0;
                }
                if ($willpower) {
                    $this->willpower_unhandled = 0;
                }
                if ($observation) {
                    $this->observation_unhandled = 0;
                }


            }
            
        }
        
        function unhandled_action() {
            return ($this->empathy_unhandled or $this->tech_unhandled or $this->running_unhandled or $this->combat_unhandled or $this->willpower_unhandled or $this->observation_unhandled);
        }
        
        function location_event_state_string($db) {
            $sql = "SELECT text FROM story_events where story_id = '{$this->story_id}' AND story_number_id = '{$this->event_id}'";
            $text = select_sql_column($sql, "text", $db);
            return "$this->event_id ($text)";
        }
        
        function exploring() {
            $exploring = 1;
        }
        
        function explored() {
            $explored = 1;
        }
        
        function next_event($transition) {
            $transition_string = $transition;
            //print ("Check for $transition_string <br>");
            foreach ($this->transitions as $t) {
                $t_string = $t->transition_string();
                //print("Checking $t_string <br>");
                if ($t->label == $transition) {
                    $to_id = $t->to;
                    //print ("Returning $to_id <br>");
                    return $t->to;
                }
            }
            array_push($this->unhandled_transitions, $transition);
        }
        
        function incomplete() {
            if (!count($this->unhandled_transitions) == 0) {
                return 1;
            }
            return 0;
        }
        
        function unhandled($transition) {
            if (in_array($transition, $this->unhandled_transitions)) {
                return 1;
            }
            return 0;
        }
    }
    
    class transition
    {
        public $from;
        public $label;
        public $to;
        
        function __construct($f, $l, $t) {
            $this->from = $f;
            $this->label = $l;
            $this->to = $t;
        }
        
        
        function print_transition() {
            print $this->from->story_state_string();
            print " -$this->label-> ";
            print $this->to->story_state_string();
            print "<br>";
        }
        
    }
  
    class local_transition
    {
        public $from;
        public $label;
        public $to;
        
        function __construct($f, $l, $t) {
            $this->from = $f;
            $this->label = $l;
            $this->to = $t;
            //print ($this->transition_string());
            //print "<br>";
        }
        
        function transition_string() {
            return "plink $this->from -$this->label-> $this->to";
        }
    
        
    }
    
    
    function get_transition_id($event_id, $action_id, $story_id, $db) {
        $sql = "SELECT transition_id FROM story_transitions WHERE story_id = '{$story_id}' AND event_id = '{$story_number_id} AND action_id = '{$action_id}'";
        return select_sql_column($sql, "transition_id", $db);
    }
    
    function transitions_complete_for_modifier_and_action($event_id, $action_id, $story_id, $modifiers, $db) {
        // $modifiers = "test";
        $sql = "SELECT probability FROM story_transitions WHERE story_id = '{$story_id}' AND event_id = '{$event_id}' AND action_id = '{$action_id}' AND modifiers = '{$modifiers}'";
        //print($sql);
        //print("<br>");
        if (!$result = $db->query($sql))
            showerror($db);
        $total_probability = 0;
        while ($row=$result->fetch_assoc()) {
            $probability = $row["probability"];
            $total_probability = $total_probability + $probability;
        }
        
        if ($total_probability == 100) {
            return 1;
        }
        return 0;
    }
    
    function print_transition($transition_id, $story_id, $db) {
        $sql = "SELECT * FROM story_transitions WHERE transition_id = '{$transition_id}'";
        
        $label = select_sql_column($sql, "transition_label", $db);
        $outcome = select_sql_column($sql, "outcome", $db);
        $modifiers = select_sql_column($sql, "modifiers", $db);
        $probability = select_sql_column($sql, "probability", $db);
        $outcome_text = select_sql_column($sql, "outcome_text", $db);
        $action_id = select_sql_column($sql, "action_id", $db);
        $event_id = select_sql_column($sql, "event_id", $db);

        $total_prob = probability_sum($story_id, $modifiers, $action_id, $event_id, $db);
        
        $sql = "SELECT text FROM story_events where story_id = '{$story_id}' AND story_number_id = '{$outcome}'";
        $text = select_sql_column($sql, "text", $db);

        $font_color = "black";
         if ($total_prob != 100) {
             print $total_prob;
             $font_color = "red";
         }

        print "<p style=\"color:$font_color\">[$modifiers] -$label ($probability) -> $outcome ($text) : $outcome_text</p>";
        
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
        
        print "<hr>";


    }
    
    function print_transitions_for_action($action_id, $story_id, $story_number_id, $event_location, $db) {
        $sql = "SELECT transition_id FROM story_transitions WHERE story_id = '{$story_id}' AND event_id = '{$story_number_id}' AND action_id = '{$action_id}'";
        //print($sql);
        
        if (!$result = $db->query($sql))
            showerror($db);
        while ($row=$result->fetch_assoc()) {
            $transition_id = $row["transition_id"];
            print_transition($transition_id, $story_id, $db);
        }
        
        print "<form method=\"POST\" action=\"edit_transition.php\">";
        print "<input type=\"hidden\" name=\"story_id\" value=\"$story_id\">";
        print "<input type=\"hidden\" name=\"task\" value=\"new_transition\">";
        print "<input type=\"hidden\" name=\"action_id\" value=\"$action_id\">";
        print "<input type=\"hidden\" name=\"event_id\" value=\"$story_number_id\">";
        print "<input type=\"hidden\" name=\"location_id\" value=\"$event_location\">";
        print "<input type=\"submit\" value=\"Add Transition\">";
        print "</form>";

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
    
    function probability_sum($story_id, $modifiers, $action_id, $event_id, $db) {
        $sql = "SELECT transition_id from story_transitions where story_id='{$story_id}' and modifiers = '{$modifiers}' and action_id = '{$action_id}' and event_id = '{$event_id}'";
        //print $sql;
        $other_transitions = sql_return_to_array($sql, "transition_id", $db);
        $total_prob = 0;
        // $outcome_list = "";
        foreach ($other_transitions as $transition) {
            $sql = "SELECT probability from story_transitions where story_id='{$story_id}' and transition_id = '{$transition}'";
            $o_probability = select_sql_column($sql, "probability", $db);
            // $outcome_list = $outcome_list . "<li>$outcome ($o_probability)</li>";
            $total_prob = $total_prob + $o_probability;
        }
        return $total_prob;
    }
    
    function probability_outcomes($story_id, $modifiers, $action_id, $event_id, $db) {
         $sql = "SELECT transition_id from story_transitions where story_id='{$story_id}' and modifiers = '{$modifiers}' and action_id = '{$action_id}' and event_id = '{$event_id}'";
         //print $sql;
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

    
?>
