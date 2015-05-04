<?php
/**
* Brute Force Search Class
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.

* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*
*
*     __     __)               _____                      ___
*    (, /|  /  ,              (, /     /)           ,    (,   )      /)    /)
*      / | /    ___   _         /  ___(/   _   __            / _   _(/  _ (/
*   ) /  |/  _(_// (_(_(_   ___/__(_) / )_(_(_/ (__(_      _/_(_(_(_(__(/_/ )_
*  (_/   '                /   /                         )   /
*                        (__ /                         (__ /
* ****************************************************************************
* @author    Nima Johari Zadeh <nijoza91-at-gmail-dot-com>
* @package   Brute Force Search Class
* @file      brute_force.class.php
* @copyright (c) 2007 Nima Johari Zadeh
*/

class brute_force
{
    var $callback_break = false;
    var $min = 0;
    var $max = 0;
    var $callback = "";
    var $_error = "";
    var $_chars = array();

    function brute_force($callback, $min, $max, $chars="all")
    {
        $this->min = $min;
        $this->max = $max;
        $this->callback = $callback;

        if(!is_int($this->min) || $this->min < 1)
        {
            $this->_error = "Invalid number for minimum characters";
            return false;
        }
        elseif(!is_int($this->max) || $this->max < $this->min)
        {
            $this->_error = "Invalid number for maximum Characters";
            return false;
        }
        elseif(!is_callable($this->callback))
        {
            $this->_error = "Callback function is not callable";
            return false;
        }

        $c['lower'] = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z");
        $c['upper'] = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $c['num']   = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9");

        if(is_array($chars))
        {
            $this->_chars = array_unique($chars);
            sort($this->_chars);
        }
        else
            switch(strtolower($chars))
            {
                case "lower":
                case "upper":
                case "num":
                    $this->_chars = $c[$chars];
                    break;

                default:
                case "alnum":
                    $this->_chars = array_merge($c['lower'], $c['upper'], $c['num']);
                    break;

                case "lalnum":
                    $this->_chars = array_merge($c['lower'], $c['num']);
                    break;

                case "ualnum":
                    $this->_chars = array_merge($ch['upper'], $c['num']);
                    break;
            }
    }

    function errormsg()
    {
        if($this->_error == "")
            return false;
        else
            return $this->_error;
    }

    function search()
    {
        if(!$this->errormsg())
        {
            $iteration = 0;
            $flags = array();
            $total_chars = count($this->_chars);
            $lendone = "";
            $total_strings = "";
            $current_string = "";
            $first_string = "";
            for($i = 0; $i < ($this->max + 1); $i++)
                $flags[$i] = -1;

            for($i = 0; $i < $this->max; $i++)
                $total_strings .= $this->_chars[$total_chars - 1];

            for($i = 0; $i < $this->min; $i++)
                $flags[$i] = $this->_chars[0];

            $i = 0;
            while($flags[$i] != -1)
            {
                $first_string .= $flags[$i];
                $i++;
            }
            $start = true;
            $iteration++;
            if(!(call_user_func($this->callback, $first_string, $iteration) && $this->callback_break))
                while(true)
                {
                    for($i = 0; $i < ($this->max + 1); $i++)
                        if($flags[$i] == -1)
                            break;
                    $i--;
                    $lendone = 0;
                    while(!$lendone)
                    {
                        for($j = 0; $j < $total_chars; $j++)
                            if($flags[$i] == $this->_chars[$j])
                                break;

                        if($j == ($total_chars - 1))
                        {
                            $flags[$i] = $this->_chars[0];
                            $i--;
                            if($i < 0)
                            {
                                for($i = 0; $i < ($this->max + 1); $i++)
                                    if($flags[$i] == -1)
                                        break;

                                $flags[$i] = $this->_chars[0];
                                $flags[$i + 1] = -1;
                                $lendone = 1;
                            }
                        }
                        else
                        {
                            $flags[$i] = $this->_chars[$j + 1];
                            $lendone = 1;
                        }
                    }
                    $i = 0;
                    $current_string = "";
                    while($flags[$i] != -1)
                    {
                        $current_string = $current_string . $flags[$i];
                        $i++;
                    }
                    $iteration++;
                    if((call_user_func($this->callback, $current_string, $iteration) && $this->callback_break) || $current_string == $total_strings)
                        break;
                }
        }
        else
            return false;
    }
}
?>