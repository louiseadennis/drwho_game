<?php
    // Representation of a story as a finite state machine where states are
    // tuples of events (one for each location in the story)
    class story_automaton
    {
        // Story states as a FIFO list
        public $states = array();
        public $transition_function = array();
        // Events in story (as numbers - event_ids)
        //public $events = array();
        // Events in story as objects
        public $event_objects = array();
        
        function __construct($story_id, $db) {
            // These are all the events where someone is present
            $locations = get_story_locations($story_id, $db);
            
            $event_sets = array();
            foreach ($locations as $location) {
                // Event 1 is the intial event at this story location
                $event1 = get_initial_event($story_id, $location, $db);
                 
                // All this adds event 1 to the current event set (suspect overkill here from when I thought locations could have multiple initial events)
                if (count($event_sets) == 0) {
                    $event_array1 = array($event1);
                    array_push($event_sets, $event_array1);
                } else {
                    $new_event_sets = array();
                    foreach ($event_sets as $event_set) {
                        array_push($event_set, $event1);
                        array_push($new_event_sets, $event_set);
                    }
                    $event_sets = $new_event_sets;
                }
            }
            
            // This should get rid of the event set where no characters are present anywhere.
            // ???
            // array_pop($event_sets);
            
            foreach($event_sets as $events) {
                $state = new story_state($events, $story_id, $db, $this);
                array_push($this->states, $state);
            }
                        
            $this->construct_automaton($this->states[0], $db);
        }
        
        function construct_automaton($state, $db) {
            $state->exploring();
            $state_string = $state->story_state_string();
            //print "Finding next states for $state_string<br>";
            
            foreach ($state->events as $event) {
                $event_state_string = $event->location_event_state_string($db);
                while (!$state->fully_explored($event)) {
                    $next_transition = $state->next_unexplored_transition($event); // don't forget to remove unexplored transition from state
                    // Null transitions are no change
                    if ($next_transition != "null") {
                        $transition_string = $next_transition;
                        $new_state = $state->next_state($next_transition, $db);
                        $new_state_string = $new_state->story_state_string();
                        
                        if (!$this->state_member($new_state)) {
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
        
        // Is this story state already in the automaton?
        function state_member($new_state) {
            foreach ($this->states as $state) {
                if ($state->is_equal($new_state)) {
                    return 1;
                }
            }
            return 0;
        }
        
        // Return the state in the automaton that is the same as this state.
        function get_state_matching($new_state) {
            foreach ($this->states as $state) {
                if ($state->is_equal($new_state)) {
                    return $state;
                }
            }
            return 0;
        }
        
        // Has we started exploring this state yet?
        function unexplored_state() {
            foreach ($this->states as $state) {
                if ($state->under_exploration == 0) {
                    return 1;
                }
            }
            return 0;
        }
        
        // Get the next unexplored state off the list.
        function get_next_state() {
            foreach ($this->states as $state) {
                if ($state->under_exploration == 0) {
                    return $state;
                }
            }
        }
        
        // Get the event object that has this event_id
        //function get_event($event_id) {
        //    foreach ($this->events as $event) {
        //        if ($event->event_id == $event_id) {
        //            return $event;
       //         }
       //     }
      //  }

        // Is there an object in our event_object list that has this event_id?
        function event_object_exists($event_id) {
            foreach ($this->event_objects  as $event_object) {
                if ($event_object->event_id == $event_id) {
                    return 1;
                }
            }
            return 0;
        }
        
        // Get the event object that has this event_id
        function get_event_object($event_id) {
            foreach ($this->event_objects  as $event_object) {
                if ($event_object->event_id == $event_id) {
                    return $event_object;
                }
            }
            print ("WARNING");
            return null;
        }
        
        // Get the event object for this event_id from the automaton
        // Create a new event object if it does not already exist.
        function get_location_event($event, $story_id, $db) {
            if ($this->event_object_exists($event)) {
                return $this->get_event_object($event);
            } else {
                $event_object = new location_event_state($event, $story_id, $db);
                array_push($this->event_objects, $event_object);
                return $event_object;
            }
        }

        
        
    }
    
    // Story state as a tuple of events - one for each location
    // Each event may have a number of transitions leading to a new state
    // Ideally, for each transition from some event in a tuple, there will be a transition from all other events in the tuple
    // but during story construction this may not be the case.
    // The next story state in a story are reached by a synchronised transition on all events in this tuple
    // Obviously a story state may have multiple next states, but it has a unique next state for each transition
    class story_state
    {
        public $events = array();
        public $unexplored_transitions = array();
        public $under_exploration = 0;
        public $story_id;
        public $automaton;
        
        // Create an empty story state
        function __construct()
        {
            $a = func_get_args();
            $i = func_num_args();
            if (method_exists($this,$f='__construct'.$i)) {
                call_user_func_array(array($this,$f),$a);
            }
        }
        
        // Construct a story state from a list of events
        function __construct3($event_list, $story_id, $automaton) {
            $this->automaton = $automaton;
            $this->story_id = $story_id;
            foreach ($event_list as $event) {
                array_push($this->events, $event);
                // If this event isn't a placeholder for the end of some transition that hasn't been defined on a particular starting event
                if ($event->event_id != 0) {
                    $this->unexplored_transitions[$event->event_id] = $event->transition_labels;
                }
                if (!in_array($event, $automaton->event_objects)) {
                    array_push($automaton->event_objects, $event);
                }
                
             }
            
            $this->remove_transitions_that_dont_need_to_synchronise();
        }

        // Construct a story state from a list of event ids
        function __construct4($event_list, $story_id, $db, $automaton) {
            foreach ($event_list as $event) {
                $location_event = $automaton->get_location_event($event, $story_id, $db);
                array_push($this->events, $location_event);
                $event_string = $location_event->location_event_state_string($db);

                // If this event isn't a placeholder for the end of some transition that hasn't been defined on a particular starting event
                // Add all the transitions associated with that event to the set of unexplored transitions from this story state.
                if ($event != 0) {
                    $this->unexplored_transitions[$event] = $location_event->transition_labels;
                }
                $this->story_id = $story_id;
                $this->automaton = $automaton;
            }
            $this->remove_transitions_that_dont_need_to_synchronise();

        }
        
        // Sometimes, when constructing a state, we have an event which is associated with a transition t where that event doesn't initiated t (i.e., the transition is associated with the event because of some other story state).  If no event in this story state actually initiates that transition then we want to prune it, because it is not relevant here.
        
        // Transitions are considered "controlled" by a particular event - i.e., that transition only takes place if the user makes an
        // action in the location assocated with that event.  Other events need a transition that synchronises with that one, but that
        // transition only takes place if the action is performed in the other location.
        function remove_transitions_that_dont_need_to_synchronise() {
            foreach($this->events as $event) {
                 if ($this->unexplored_transitions[$event->event_id] != null) {
                    foreach ($this->unexplored_transitions[$event->event_id] as $transition_label) {
                        $remove = 1;
                        
                        // If there is any event that can initiate this transition then it should not be removed from the unexplored_transitions list.
                        foreach($this->events as $event2) {
                            foreach($event2->transitions as $transition) {
                                if ($transition->label == $transition_label) {
                                   if ($transition->not_controlled == 0) {
                                         $remove = 0;
                                     }
                                }
                            }
                        }
                        
                        // Remove any transition that can't be initiated by any event in this state
                        if ($remove == 1  && $event->event_id != 0) {
                            $string = $this->story_state_string();
                            $this->unexplored_transitions[$event->event_id] = array_diff($this->unexplored_transitions[$event->event_id], [$transition_label]);
                         }
                    }
                }
            }
        }
        
        // Are two states actually equal? i.e., tuples of the same set of events.
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

        // Have all transitions associated with this event been explored and next story states constructed for them?
        function fully_explored($event) {
            $event_id = $event->event_id;
             if ($event->event_id == 0) {
                return 1;
            }
            if (count($this->unexplored_transitions[$event_id]) == 0) {
                return 1;
            } else {
                return 0;
            }
        }
        
        // Get the next unexplored transition
        function next_unexplored_transition($event) {
            $transition = array_pop($this->unexplored_transitions[$event->event_id]);
            
            return $transition;
        }
        
        // Note that this story state is in the process of having its next states constructed
        function exploring() {
            $this->under_exploration = 1;
        }
        
        // This story state now has all next states constructed
        function explored() {
            $this->under_exploration = 2;
        }
        
        // Return the next state for this transition on this story state.
        function next_state($transition, $db) {
            $event_list = array();
            foreach ($this->events as $event) {
                $next_event = $event->next_event($transition);
                if (!$event->unhandled($transition)) {
                    array_push($event_list, $this->automaton->get_location_event($next_event, $this->story_id, $db));
                    
                    if (in_array($transition, $this->unexplored_transitions[$event->event_id])) {
                        $this->unexplored_transitions[$event->event_id] = array_diff($this->unexplored_transitions[$event->event_id], [$transition]);
                    }
                } else {
                    // If the event has no next event for this transition then use the dummy "0" event to indicate this fact
                    // For the story to be complete at some point the next event will need to be determined.
                    array_push($event_list, $this->automaton->get_location_event(0, $this->story_id, $db));
                }
            }
 
            return new story_state($event_list, $this->story_id, $this->automaton);
        }
        
        // Return the story state as a string
        function story_state_string() {
            $out_string = "<";
            foreach ($this->events as $event) {
                $out_string .= "$event->event_id,";
            }
            $out_string .= ">";
            return $out_string;
        }
        
    }
    
    // Class for events appearing in the automaton.
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
            if ($success_fail != 1) {
                $this->end_state = 1;
            }
            
            if ($event != 0 and !$this->end_state) {
                $sql = "SELECT transition_label, outcome, action_id, modifiers FROM story_transitions WHERE event_id = '{$this->event_id}' and story_id = '{$story_id}'";
                if (!$result = $db->query($sql))
                    showerror($db);
                $empathy = 0;
                $tech = 0;
                $running = 0;
                $combat = 0;
                $willpower = 0;
                $observation = 0;
                while ($row=$result->fetch_assoc()) {
                    if (!in_array($row["transition_label"], $this->transition_labels)) {
                        array_push($this->transitions, new local_transition($this->event_id, $row["transition_label"], $row["outcome"], $row["action_id"]));
                        array_push($this->transition_labels, $row["transition_label"]);
                    }
                    
                    // We need to track if all the default actions are handled by some transition
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
        
        // One of the default actions is not associated with a transition for this event.
        function unhandled_action() {
            return ($this->empathy_unhandled or $this->tech_unhandled or $this->running_unhandled or $this->combat_unhandled or $this->willpower_unhandled or $this->observation_unhandled);
        }
        
        function location_event_state_string($db) {
            $sql = "SELECT text FROM story_events where story_id = '{$this->story_id}' AND story_number_id = '{$this->event_id}'";
            $text = select_sql_column($sql, "text", $db);
            return "$this->event_id ($text)";
        }
        
        function location_event_state_string_long() {
            $string = "$this->event_id (--)";
            foreach ($this->unhandled_transitions as $transition) {
                $string = $string . "<br>" . $transition;
            }
            $string = $string . " END<br>";
            return $string;
        }

        // We are in the process of finding the next events for this event
        function exploring() {
            $exploring = 1;
        }
        
        // We have found all the next events for this event
        function explored() {
            $explored = 1;
        }
        
        // Return the next event for this transition.  If no next event exists then not the transition as unhandled.
        function next_event($transition) {
            $transition_string = $transition;
            foreach ($this->transitions as $t) {
                $t_string = $t->transition_string();
                 if ($t->label == $transition) {
                    $to_id = $t->to;
                    return $t->to;
                }
            }
             if (!in_array($transition, $this->unhandled_transitions)) {
                array_push($this->unhandled_transitions, $transition);
            }
         }
        
        // This event has unhandled transitions that need to be specified
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
    
    // Class for global transitions on story states
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
  
    // Class for local transitions on an event - note whether the event can control/initiate the transition
    class local_transition
    {
        public $from;
        public $label;
        public $to;
        public $not_controlled = 0;
        
        function __construct($f, $l, $t, $action_id) {
            $this->from = $f;
            $this->label = $l;
            $this->to = $t;
            if ($action_id == 0) {
                $this->not_controlled = 1;
            }
        }
        
        function transition_string() {
            return "plink $this->from -$this->label-> $this->to ($this->not_controlled)";
        }
    
        
    }
    

    
?>
