<?php
/**
 * Plugin Name: Hide Update Nag
 * Description: Hides the WordPress update nag banner.
 * Version: 0.1
 * Author: Chris Reynolds
 * Author URI: https://github.com/jazzsequence
 * License: MIT

 * MIT License

 * Copyright (c) 2022 Chris Reynolds <hello@chrisreynolds.io>

 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace jz\Admin;

function bootstrap() {
  add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\admin_scripts' );
}

function admin_scripts() {
  wp_add_inline_style( 'admin-color-scheme', '.update-nag.notice-warning.inline { display: none !important; }' );
}

bootstrap();
