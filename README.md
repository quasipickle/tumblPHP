#tumblPHP


A simple PHP-based Tumblr theme parser.  Intended to be run either locally or on a web server you can edit files on.

###Please let me know if you're interested in using this project!
There are a lot of rough edges that don't bother me, but might bother you.  Just let me know and I'll be happy to work with you to get it usable. I didn't want to waste my time if no one else is going to use this.

##How to use
1. Download all the files
2. *data.json* contains all the mock data for your blog. Tweak/change it to accommodate the type of blog data you want to simulate
3. *template.html* contains your Tumblr theme.  You should be able to do a straight transfer between Tumblr and this file (and vice versa) without any changes.
4. Profit!

##Caveats
There are a lot of them:  

- I tried my best to duplicate the behaviour of blocks, but I was just reverse engineering from the documentation.  If you find the blocks in tumblPHP are activated in different situations than Tumblr, let me know

- Localization strings aren't converted.  So if your theme has {lang:About}, your rendered page will still have {lang:About}.  I didn't bother translating because there are a billion strings.

- Likes are not implemented.  I wasn't able to get my theme to show my likes, so I couldn't extract the markup.

- Lightboxes aren't implemented.  They don't affect a theme anyway.

- Link/href variables don't go anywhere.  They'll be replaced properly, but the value will just be anchor links like #next-page, 

- Audio: all audio posts get a SoundCloud player embedded. Tumblr does a bunch of funkiness with an iframe that I didn't really feel like replicating.  There is a way to simulate both {AudioEmbed} and {AudioPlayer} variables though - see data.json

- Photosets: Using {Photoset}, {Photoset-500}, etc just generates a hardcoded photoset.  It has the same layout as a Tumblr photoset. Tumblr uses an embedded iframe and I didn't want to duplicate it.

- Videos: All video posts show a YouTube clip.  There is a way to simulate a Tumblr-hosted video - see data.json
