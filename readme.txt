=== Disable Meta Box Resizing ===
Contributors: acato, paulacato
Tags: editor, meta boxes, block editor, preferences, custom fields
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn off the resizable metabox panel that WordPress 7.0 added to the bottom of the post editor. Set per user.

== Description ==

WordPress 7.0 turned the metabox area at the bottom of the post editor into a collapsible, drag-to-resize panel. It is a nice addition for some, and a constant source of accidental drags and hidden fields for others.

This plugin gives every user their own switch. Turn it on and the panel goes back to how it behaved before WordPress 7.0: no drag handle, no collapse toggle, the metaboxes simply sit below a full height canvas again.

Because they sit below the canvas, they start off screen. A **Metaboxes** bar stays on the bottom edge of the editor to say they are there: press it to jump to them, press it again to come back to the content. It only scrolls out of the way once you have scrolled past it yourself.

The setting is stored per user, so one editor can keep the resizable panel while another turns it off. Nothing changes for anyone who leaves the option alone.

= Where a user sets their own preference =

Two places, both writing the same value and both applying immediately without reloading the editor:

* **Editor → Options (⋮) → Disable metabox resizing.** Reachable in one click while writing.
* **Editor → Options (⋮) → Preferences → Appearance → Metaboxes.**

And one place outside the editor:

* **Users → Profile → Editor metaboxes.** Useful when an administrator sets it up on behalf of someone else.

= What an administrator can configure =

Under **Settings → Meta Box Resizing** (or **Network Admin → Settings → Meta Box Resizing** on multisite):

* **Default state** — whether resizing starts out disabled for users who have not chosen for themselves. Users who did choose keep their own setting.
* **Uninstall** — whether to delete the plugin's settings and every stored user preference when the plugin is uninstalled. Off by default, so nothing is lost on a reinstall.
* **Users list** — adds a read only column to the users overview showing each user's setting, plus a dropdown to filter the list on it.

On multisite the settings live in the network admin, and each site's Settings menu links through to them.

The plugin's row on the **Plugins** screen has a **Settings** link straight to that screen, for anyone who can open it.

A short walkthrough shows an administrator around the settings screen the first time they open it. Until they do, a card beside the **Settings** menu points the way; **Not now** puts it off. It can be taken again at any time from the link at the bottom of the screen, and after an update that adds steps, only the new ones are shown.

= Display modes =

The settings screen and the walkthrough follow the way each administrator has set up their computer and their profile:

* **Dark mode** — when the operating system is set to dark, the plugin's settings screen turns dark as a whole.
* **More or less contrast** — stronger text and lines, or softer ones, in both light and dark.
* **Admin colour scheme** — the accent follows the scheme chosen on the profile, darkened where it would otherwise be hard to read.
* **Forced colours** (Windows High Contrast) — the system's own colours take over, and cards keep a visible edge.
* **Reduced motion** — no animations, and the walkthrough scrolls without smoothing.

The Metaboxes bar in the editor follows the contrast and forced colours settings too.

= Good to know =

* Development happens at [github.com/acato-plugins/disable-meta-box-resizing](https://github.com/acato-plugins/disable-meta-box-resizing). The uncompiled sources of the editor script and stylesheet ship in `/src`, next to the compiled files in `/build`.
* The plugin only touches the post editor. The site editor has no metaboxes and is left alone.
* Two pieces of user meta (the preference, and which walkthrough a user has seen) and one settings array. No front end code.
* On WordPress older than 7.0 the plugin deactivates itself and explains why, since there is no resizable panel there to switch off.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install it through **Plugins → Add New**.
2. Activate the plugin through the **Plugins** screen. On multisite you can network activate it.
3. Optionally set the site wide defaults under **Settings → Meta Box Resizing**.
4. Each user can then set their own preference from the editor's Options menu or their profile.

== Frequently Asked Questions ==

= Does this change the setting for everybody? =

No. The preference is stored per user, so each person chooses for themselves. An administrator only sets the starting point for users who have not chosen yet.

= I turned it on but the drag handle is still there. =

The editor applies the change straight away. If the handle is still there after a reload, another plugin is probably enqueueing styles that override this one, or the metabox panel is being rendered by something other than the classic post editor.

= Can I set this for a user who is not me? =

Yes, if you may edit that user. Open their profile under **Users → All Users** and use the **Editor metaboxes** option there.

= Does the editor option work on small screens? =

The Options menu item and the profile setting work everywhere. The section in the Preferences modal appears on screens wide enough for the modal's tabbed layout, which is the same breakpoint WordPress itself uses.

= Will uninstalling lose everyone's preference? =

Only if you ask for it. Data removal on uninstall is off by default; switch it on under the plugin's settings if you want a clean removal.

= Where are the translations? =

Translations are managed on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/disable-meta-box-resizing/) and are downloaded by WordPress automatically. The plugin ships a `.pot` template for translators, but no locale files of its own.

= How can I report security bugs? =

You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/c0a4068d-1475-4ec9-8550-85984214d223)

== Screenshots ==

1. The Metaboxes section on the Appearance tab of the editor's Preferences dialog.
2. The settings screen, where an administrator sets the default and the extras.
3. The post editor with resizing disabled: the metaboxes sit below a full height canvas, with no drag handle and no collapse toggle, and the Metaboxes bar on the bottom edge leading to them.

== Changelog ==

= Unreleased =
* Wording: "meta box" is now written "metabox" in the interface, following the WordPress translation glossary. The plugin name stays the same. Translations of the changed texts need to be done again.

= 1.1.1 =
* Fixed the arrows on the Meta boxes bar, and the checkmark in the Options menu, which 1.1.0 had changed to thin outlines along with its tooling update. They look as they did before again.
* Build tooling only: the remaining vulnerable development packages are pinned to fixed versions. Nothing of it ships with the plugin.

= 1.1.0 =
* Added a walkthrough of the settings screen, on WordPress's own admin pointers. A card beside the Settings menu leads to it until it is taken or put off with **Not now**, and it can be taken again from the bottom of the screen.
* Added a dark version of the settings screen, following the operating system, and colours for more and less contrast, forced colours (Windows High Contrast) and every admin colour scheme. The walkthrough honours reduced motion.
* The Meta boxes bar in the editor follows the more contrast and forced colours settings.
* Uninstalling with data removal on also forgets which walkthrough each user has seen.
* Updated the build tooling to the latest WordPress packages on Node 24.

= 1.0.7 =
* Added a Playground blueprint, so the plugin directory can offer a **Live Preview**: it opens the editor with resizing switched off, a few demo meta boxes below the canvas and the site wide defaults already set. No change to the plugin itself.

= 1.0.6 =
* Added a **Settings** link to the plugin's row on the Plugins screen, so the settings screen is reachable without going looking for it in the menu. On multisite it leads to the network settings screen, and only shows for users who can actually open it.

= 1.0.5 =
* Fixed the meta boxes staying hidden, and the Meta boxes bar leading nowhere, for anyone who had never dragged the panel open before disabling resizing. WordPress backs the collapsed state with an `!important` rule, which the plugin's stylesheet did not outrank.

= 1.0.4 =
* Lowered the PHP requirement from 8.3 to 8.2, so the plugin installs on sites that are still on PHP 8.2. The code itself is unchanged; it never used anything PHP 8.3 introduced.

= 1.0.3 =
* Added the Patchstack Vulnerability Disclosure Program to the readme, so security issues have a clear reporting route.

= 1.0.2 =
* No changes, re-tagged for deployment purposes.

= 1.0.1 =
* Added a Meta boxes bar on the bottom edge of the editor when resizing is disabled, so the meta boxes below the canvas are no longer easy to miss. Press it to jump to them and again to come back.
* Fixes [#2](https://github.com/acato-plugins/disable-meta-box-resizing/issues/2).

= 1.0.0 =
* Initial release.
* Per user preference to disable the resizable meta box panel.
* Available from the editor's Options menu, the Preferences modal and the user profile.
* Site wide settings for the default state, data removal on uninstall and the users list column.
* Filter the users overview by the preference.
* Deactivates itself with an explanation on WordPress older than 7.0.
