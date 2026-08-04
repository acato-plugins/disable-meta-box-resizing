=== Disable Meta Box Resizing ===
Contributors: acato, paulacato
Tags: editor, meta boxes, block editor, preferences, custom fields
Requires at least: 7.0.0
Tested up to: 7.0.2
Requires PHP: 8.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn off the resizable meta box panel that WordPress 7.0 added to the bottom of the post editor. Set per user.

== Description ==

WordPress 7.0 turned the meta box area at the bottom of the post editor into a collapsible, drag-to-resize panel. It is a nice addition for some, and a constant source of accidental drags and hidden fields for others.

This plugin gives every user their own switch. Turn it on and the panel goes back to how it behaved before WordPress 7.0: no drag handle, no collapse toggle, the meta boxes simply sit below a full height canvas again.

The setting is stored per user, so one editor can keep the resizable panel while another turns it off. Nothing changes for anyone who leaves the option alone.

= Where a user sets their own preference =

Two places, both writing the same value and both applying immediately without reloading the editor:

* **Editor → Options (⋮) → Disable meta box resizing.** Reachable in one click while writing.
* **Editor → Options (⋮) → Preferences → Appearance → Meta boxes.**

And one place outside the editor:

* **Users → Profile → Editor meta boxes.** Useful when an administrator sets it up on behalf of someone else.

= What an administrator can configure =

Under **Settings → Meta Box Resizing** (or **Network Admin → Settings → Meta Box Resizing** on multisite):

* **Default state** — whether resizing starts out disabled for users who have not chosen for themselves. Users who did choose keep their own setting.
* **Uninstall** — whether to delete the plugin's settings and every stored user preference when the plugin is uninstalled. Off by default, so nothing is lost on a reinstall.
* **Users list** — adds a read only column to the users overview showing each user's setting, plus a dropdown to filter the list on it.

On multisite the settings live in the network admin, and each site's Settings menu links through to them.

= Good to know =

* Development happens at [github.com/acato-plugins/disable-meta-box-resizing](https://github.com/acato-plugins/disable-meta-box-resizing). The uncompiled sources of the editor script and stylesheet ship in `/src`, next to the compiled files in `/build`.
* The plugin only touches the post editor. The site editor has no meta boxes and is left alone.
* One piece of user meta and one settings array. No front end code.
* On WordPress older than 7.0.0 the plugin deactivates itself and explains why, since there is no resizable panel there to switch off.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install it through **Plugins → Add New**.
2. Activate the plugin through the **Plugins** screen. On multisite you can network activate it.
3. Optionally set the site wide defaults under **Settings → Meta Box Resizing**.
4. Each user can then set their own preference from the editor's Options menu or their profile.

== Frequently Asked Questions ==

= Does this change the setting for everybody? =

No. The preference is stored per user, so each person chooses for themselves. An administrator only sets the starting point for users who have not chosen yet.

= I turned it on but the drag handle is still there. =

The editor applies the change straight away. If the handle is still there after a reload, another plugin is probably enqueueing styles that override this one, or the meta box panel is being rendered by something other than the classic post editor.

= Can I set this for a user who is not me? =

Yes, if you may edit that user. Open their profile under **Users → All Users** and use the **Editor meta boxes** option there.

= Does the editor option work on small screens? =

The Options menu item and the profile setting work everywhere. The section in the Preferences modal appears on screens wide enough for the modal's tabbed layout, which is the same breakpoint WordPress itself uses.

= Will uninstalling lose everyone's preference? =

Only if you ask for it. Data removal on uninstall is off by default; switch it on under the plugin's settings if you want a clean removal.

= Where are the translations? =

Translations are managed on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/disable-meta-box-resizing/) and are downloaded by WordPress automatically. The plugin ships a `.pot` template for translators, but no locale files of its own.

== Changelog ==

= 1.0.0 =
* Initial release.
* Per user preference to disable the resizable meta box panel.
* Available from the editor's Options menu, the Preferences modal and the user profile.
* Site wide settings for the default state, data removal on uninstall and the users list column.
* Filter the users overview by the preference.
* Deactivates itself with an explanation on WordPress older than 7.0.0.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
