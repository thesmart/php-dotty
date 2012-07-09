php-dotty
=========

Access array data quickly using dot notation.

# Whats the big deal?

Getting hit in the face with a data fire-hose is painful. Enter Dotty.

	$allIds	= Dotty::with($data)->set('data.users.id')->result();

# Install

Install composer:

	$ curl -s http://getcomposer.org/installer | php
	$ sudo mv composer.phar /usr/local/bin/composer

Create a composer.json manifest:

	$ cd ~/your-project-folder
	$ echo "{}" > composer.json

Require Dotty:

	$ composer require dotty/dotty
	// specify 0.0.*

Done.

# Example

	{
	   "data": [
	      {
	         "name": "Farenheit 451",
	         "category": "Book",
	         "id": "114836791862134",
	         "created_time": "2010-09-30T19:36:29+0000"
	      },
	      {
	         "name": "Coders at work",
	         "category": "Book",
	         "id": "104178566283955",
	         "created_time": "2010-09-30T19:20:50+0000"
	      },
	      {
	         "name": "Age of Spiritual Machines",
	         "category": "Book",
	         "id": "114114031939540",
	         "created_time": "2010-09-30T19:20:50+0000"
	      },
	      {
	         "name": "The Internets",
	         "category": "Book",
	         "id": "104303812934779",
	         "created_time": "2010-05-03T19:36:18+0000"
	      },
	      {
	         "name": "Ender's Game",
	         "category": "Book",
	         "id": "108178452537550",
	         "created_time": "2010-05-03T19:36:18+0000"
	      },
	      {
	         "name": "Catcher In The Rye",
	         "category": "Book",
	         "id": "115946728419025",
	         "created_time": "2010-05-03T19:36:18+0000"
	      },
	      {
	         "name": "George Orwell",
	         "category": "Author",
	         "id": "108663695825202",
	         "created_time": "2010-05-03T19:36:18+0000"
	      }
	   ]
	}
	
**TAME THE BEAST!**

	$data	= json_decode($data, true);
	$names	= Dotty::with($data)->set('data.name')->result();
	echo json_encode($names);

	["Farenheit 451","Coders at work","Age of Spiritual Machines","The Internets","Ender's Game","Catcher In The Rye","George Orwell"]

	// chain commands together
	Dotty::with($data)->one('data')->set('name');

	["Farenheit 451","Coders at work","Age of Spiritual Machines","The Internets","Ender's Game","Catcher In The Rye","George Orwell"]

# Dotty Notation

Notation is dotty is simple. Consider again the dataset above:

	// finds a specific value at an exact address
	Dotty::with($data)->one('data[1].name');

	// finds the first sub-node matching a key
	Dotty::with($data)->first('name');

	// finds all sub-nodes matching a key (even at different levels in the data)
	Dotty::with($data)->all('name');

**WARNING** above, when I say pulls *ALL* sub-nodes, does not stop at a specific level in the data tree.

	// takes everything before the last dot, and uses it for a "one" operation
	// uses the last symbol, uses it for an "all" operation
	Dotty::with($data)->set('data.name');

# Mutation

With Dotty, you can also modify a data-set easily.  Consider again the dataset above:

	echo $data['data'][1]['name'];
	echo "\n";

	// correct the title casing so that "work" is upercase
	$names		= &Dotty::with($data)->set('data.name');
	$names[1]	= mb_convert_case($names[1], MB_CASE_TITLE);
	
	echo $names[1];
	echo "\n";
	echo $data['data'][1]['name'];

Behold, the power of the dark side:

	Coders at work
	Coders at Work
	Coders at Work

**NOTE** you have to designate that you want Dotty's return value by reference

	&Dotty::with($data)...
