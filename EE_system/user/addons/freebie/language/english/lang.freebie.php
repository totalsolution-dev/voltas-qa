<?php

/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		http://ee.reinos.nl
 *
 * This is free and unencumbered software released into the public domain.
 *
 * Anyone is free to copy, modify, publish, use, compile, sell, or
 * distribute this software, either in source code form or as a compiled
 * binary, for any purpose, commercial or non-commercial, and by any
 * means.
 *
 * In jurisdictions that recognize copyright laws, the author or authors
 * of this software dedicate any and all copyright interest in the
 * software to the public domain. We make this dedication for the benefit
 * of the public at large and to the detriment of our heirs and
 * successors. We intend this dedication to be an overt act of
 * relinquishment in perpetuity of all present and future rights to this
 * software under copyright law.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
 * OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

$lang = array(
  'to_ignore' => '<h4>Freebie segments:</h4>
                  <p style="font-weight: normal">
                    EE will act as if these segments aren&rsquo;t in the URI at all.</p>
  
                      <div style="margin: 20px 0 0; border-top: 1px solid #ccc; font-weight: normal; font-style: italic">
                        <ul style="list-style: none">
                          <li style="margin: 10px 0 10px">
                            success|error|preview
                          </li>
                          <li style="margin: 10px 0 10px">
                            success error preview
                          </li>
                          <li style="margin: 10px 0 10px">
                            success<br />error<br />preview
                          </li>
                          <li style="margin: 10px 0 10px">
                            inky*clyde <span style="font-style:italic">
                            (matches inkyblinkypinkyclyde)</em></span>
                          </li>
                        </ul>                        
                      </div>',
                      
  'ignore_beyond' => '<h4>Breaking segments:</h4>
                      <p style="font-weight: normal">
                        All segments AFTER one of these matches will be ignored.</p>
                      
                      <p style="font-weight: normal;">
                        Example: The URI about/<strong>map</strong>/virginia/arlington/22201,
                        will process as about/<strong>map</strong> if you set <strong>map</strong> 
                        as a breaking segment</p>',
                        
  'break_category' => '<h4>Break on category URL indicator </h4>
                         <p style="font-weight: normal">
                         Set the URL indicator 
                         <a href="'.BASE.'&C=admin_content&M=global_channel_preferences">here</a>
                       </p>',
                                               
  'remove_numbers' => '<h4>Ignore numeric segments </h4>
                         <p style="font-weight: normal">
                         Examples: /2010/, /2/, /101/</p>',

  'always_parse' => '<h4>Always Parse:</h4>
                         <p style="font-weight: normal">
                           If you have segments you NEVER want Freebie to screw with, set them here.
                           (Example: search)</p>',

  'always_parse_pagination' => '<h4>Always Parse Pagination:</h4>'

);
