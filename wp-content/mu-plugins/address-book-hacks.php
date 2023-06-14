<?php
/**
 * Plugin Name: Address Book Hacks
 * Description: Currently this is just hiding the SEO meta box on Address posts.
 * Version: 0.1
 * Author: Chris Reynolds
 * Author URI: https://github.com/jazzsequence
 * License: MIT

 * MIT License

 * Copyright (c) 2023 Chris Reynolds <hello@chrisreynolds.io>

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

namespace jz\ABHack;

function bootstrap() {
	remove_meta_box( 'wp-seo', 'ab_address', 'advanced' );
	remove_meta_box( 'wp_seo', 'ab_address', 'advanced' );
}

add_action( 'admin_menu', __NAMESPACE__ . '\\bootstrap' );
