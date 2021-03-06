MEFSocketBundle
==============

Implement websockets in your symfony application.
------------------------------------------------
### Documentation

  Refer to [documentation site][8]
  
### Installation

You need to [get composer][6] and install that if you haven't already. If you don't have [symfony, go download it][7]
via the composer console command, don't be a sissy, you can do it. Now go to your symfony application root directory
and modify the composer.json.  Add this to the required section:

    "mef/socket-bundle": "dev-master"
    
Once that's been situated, run your composer command and update your application.

    php composer.phar update
    
That should do most of the dirty work for you, including registering the namespace and ensuring dependencies.  The only thing 
left is injecting the bundle into the AppKernel and setting up configuration.

Open up your app/AppKernel.php file and add the last line below.  

    $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            ...
            new MEF\SocketBundle\MEFSocketBundle()
            );
            

### Configuration

Here is an example configuration
    
    mef_socket:
        port: 4000
        host: 127.0.0.1
        
You can also run tcp and ws in parallel by specifying each in the configuration
    
    mef_socket:
        tcp:
            port: 4000
            host: 127.0.0.1
        web:
            port: 4001
            host: 127.0.0.1    


### TCP Socket Usage

After installation you should test to be sure the console command is now available

    app/console list | grep socket
    
You should see the commands for socket:listen and socket:web:listen, if you do not
then the bundle hasn't been properly installed.

Next let's test the basic tcp socket server to make sure things can spin up and ports can be bound.

    app/console socket:listen
    
If this worked, you should see a message saying "Attempted to connect to (host) on port (port)".

Now let's attempt to talk to your newly spun up server.  Open another terminal shell and use a little telnet.
I'm going to use the values stated above in the configuration section for this example so I don't have to use
variable notation.

    telnet 127.0.0.1 4000
    
If everything was successful with connecting to the socket server, then you should see this

    Trying 127.0.0.1...
    Connected to localhost.
    Escape character is '^]'.

If you see that then you're in like flynn!  If not then, what did you do!? You broke iT!!! 
Try double checking your host and port in the configuration, make sure your server is running.  Running the server is basically
executing a process that hangs, it has to, it's listening for connections, so after running app/console socket:listen
don't shut that process down!! It needs to stay alive, no Ctrl C for you!

Ok so granted that worked, now you can send it a message, like hello, what else do you say to a computer? Stop all the downloadin'?
You should be able to view your logs and see the traffic come in but it won't reply back to you... yet.

If that all worked hunky dory then you're ready for the next step, debugging and more testing.

    app/console socket:listen --debug
    
The debug flag puts listeners on the socket server and will output almost all activity to the console screen.
It truncates big messages but hello will certainly fit. Another essential flag is --test-mode.

    app/console socket:listen --debug --test-mode
    
Test mode will receive a few choice words and respond to them. The words of choice are "hello" and "thanks", when speaking to your computer
remember to keep it civil.  Try it out using telnet and make sure you're seeing the response.

### TCP Socket Testing

Use both --debug and --test-mode, debug will help tell you what's going wrong and well --test-mode is absolutely necessary, so don't forget it.
Spin up the server in one shell and execute the phpunit tests in another.  I tried getting phpunit to spin up the server itself in a separate
process but it just wasn't playing nice, it's too fast for the port binding and things just got crazy, so two shells.  

I've used phpunit groups to help limit things as you can't run all the tests at once, it just wouldn't work until 
I can get both servers to run in parallel, which isn't hard, I just have to work the configuration options a little more.  

Run this in one shell:

    app/console socket:listen --debug --test-mode
    
Run this in another shell:
    
    phpunit -c vendor/MEF --group="socketclient"
    
Hopefully you'll see the great line of green and all will be well.

### WebSocket Usage

Now let's get to the good stuff, tcp is fun for messing around with some low level stuff, maybe some server to server communications
but what we're really after here is the holy grail of RIA, [Push Technology][1].  With this we no longer have to wait for a request from the client
to give them updated information.  With a streaming websocket the browser is able to receive events from the server.  For instance one user
changes a record while another user is viewing that record, now the user who is viewing gets an updated state of data because the event 
fired on the server that the record has changed and it was pushed to the user.

Let's get to it, start up your WebSocket server with:

    app/console socket:web:listen
    
The same flags that applied to the tcp socket apply to the websocket, so --debug and --test-mode are your new best friends.
To test the websocket server, you can't use telnet because the [websocket protocol][2] is outrageous and you'd have no chance to be able
to emulate it off keyboard input.

### Testing WebSocket with phpunit

First, start up your server in full fledge test mode

    app/console socket:web:listen --debug --test-mode

I recommend using phpunit to start and then you can actually test it with your browser.

    phpunit -c vendors/MEF --group="websocket"


### Testing WebSocket with a browser

If that all worked then you can test it with your browser, so pop open Chrome or Firefox and if you thought you'd use IE please find
a short pier for your long walk. 

    var socket = WebSocket("ws://127.0.0.1:4000");
    
    socket.onmessage = function(evt){ console.log("Socket Message received %o", evt); };
    
    socket.send("hello");
    
    
If everything worked right you should receive a response back from the server with "Why hello yourself" and 
you can consider your websocket server up and running.

I will now shamelessly plug my other project [TrafficJS][4].  Which is a javascript library coupled with [Backbone.js][5]
to handle XmlHttpRequest and WebSocket communications in an elegant event driven fashion.  I will
be using it in conjunction with this bundle so you can bank on there being benefits from using both.

now get to implementing something cool with it.


[1]: http://en.wikipedia.org/wiki/Push_technology   "Push Technology"
[2]: http://tools.ietf.org/html/rfc6455             "websocket protocol"
[3]: http://getfirebug.com/                         "firebug"
[4]: https://github.com/MFoster/TrafficJS           "TrafficJS"
[5]: http://backbonejs.org/                         "Backbone.js"
[6]: http://getcomposer.org/                        "get composer"
[7]: http://symfony.com/download                    "symfony"
[8]: http://positionabsolute.net/mefsocketbundle    "documentation site"
