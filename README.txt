=== WordPress Referrers ===
Contributors: Seans0n
Donate link: http://www.seanbluestone.com
Tags: referrers, referral, stats, hits, reciprocal, links, linking
Requires at least: 2.2
Tested up to: 2.6
Stable tag: 1.2.1

WordPress Referrers is a fully automated, self running referral tracking script.
You set it up once and just let it run, the list is automatically cut after x days, you can block any domains from being included, includes spam protection and other features.

== Description ==

WordPress Referrers is a fully automated, self running referral tracking script.
You set it up once and just let it run, the list is automatically cut after x days, you can block any domains from being included, includes spam protection and other features.

Features:
 - Automated and self running
 - URLs are kept for x days
 - List is constantly updated
 - Show x referrers on any of your pages
 - Widgetized for quick adding
 - Block urls from the list
 - Change an outgoing URL to a special URL (such as a top list referral id)
 - Pick titles for your referrers instead of using the domain name
 - Tracks hits in and hits out

Related Links:

* <a href="http://www.seanbluestone.com/wordpressp-top-referrers">Plugin Homepage</a>
* <a href="http://www.seanbluestone.com">WordPress Top Referrers Demo</a>

== Installation ==

1. Extract & upload the topref folder to your '/wp-content/plugins/' directory.
2. Activate the plugin via the Plugins menu in WordPress.
3. Navigate to Design -> Widgets and add the Top Referrers widget. There are a couple of settings to set the widget up how you like.
4. Alternatively if you don't use widgets or are on an older version of WordPress you can insert this code wherever you want your top referrers displayed:

	<?php topref_log_refs(); topref_display_refs('MODE',NUMBER); ?>

You should replace MODE with either 'Top' for top x referrers or 'Last' for the last x referrers. Replace NUMBER with however many you want to display. I.e: topref_display_refs('Last',15);

Thats it! Your referrers should show up wherever you pasted the code. The sidebar is the best place because it's on every page. If you wish to log referrals on every page but only display them on one specific page then you could put <?php topref_log_refs(); ?> in your Sidebar.php and <?php topref_display_refs(); ?> on your chosen page. <?php topref_log_refs(); ?> must be included on every page you want to collect referrals on.