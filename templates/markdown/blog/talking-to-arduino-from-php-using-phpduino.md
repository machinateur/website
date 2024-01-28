This is what [phpduino](https://github.com/machinateur/phpduino) does.

```php
// First register the protocol and stream wrapper, ...
\Machinateur\Arduino\streamWrapper::register();
// ... then use the protocol in a stream function...
$fd = \fopen('arduino://', 'r+b');
// ... and finally do things with the $fd (fread/fwrite ops).
```

## Why would you even want to do such a thing?

Well, first off, why not? And then, a use-case, where a web-server application might want to communicate
 with a microcontroller device. Be it for querying information or sending commands. But such use-cases are the more
 complex ones.

In my case, I simply wanted to test out the transmission speed and benchmark the communication. It may well be useful
 to monitor serial output using just a simple script instead of an IDE. So PHP is also very versatile, when it comes to
 where to use it. From complex websites and backend processes to command line tools.

I figured it would be worth trying to implement a library to handle serial communication.

## How it works

### Using `streamWrappers`

Most of those, who have used PHP before, have likely used streams in some type or form. If it's not for reading/writing
 files, it may be for downloading files or doing FTP interactions.
There are `php://temp`orary streams and `php://memory` streams, and there are also file handles.
Luckily, connected USB devices are also represented as files,
 such as in the [`/dev` directory](https://www.baeldung.com/linux/dev-directory) on linux and mac.
Windows has [`COM1` to `COM9` ports](https://learn.microsoft.com/en-us/windows/win32/fileio/naming-a-file#nt-namespaces).

Because of that, it only made sense to implement [a custom `streamWrapper`](https://www.php.net/manual/en/class.streamwrapper.php)
 to handle the communication. As a bonus, I was relatively certain that it would be possible to
use [the `stream_select()` function](https://www.php.net/manual/en/function.stream-select.php) on the custom resource 
 type as well. Spoiler: It does work with that function, which essentially makes possible connecting to more than one
 USB devices at the same time and serving each one as it goes.

There was quite some experimentation involved in finding out which methods should be implemented and which would not be
 needed. It helped to intensely study the [documentation](https://www.php.net/manual/en/class.streamwrapper.php) on each
 of the `streamWrapper` methods, mentioned in the reference implementation there.

### Cross-platform functionality

Thankfully the configuration options do not differ that much between Windows, Linux and Mac.

Windows uses the `mode` command, Mac and Linux `stty` (but with a different CLI argument casing). There are also
 differences in when to configure the port. On Windows it has to be done *prior* to opening the connection,
 on Mac (and presumably also Linux) it has to be set only *after* the handle has be opened.

A simple check on the `\PHP_OS_FAMILY` constant does the trick to decide which concrete implementation
 of the stream wrapper class should be loaded in the current runtime. That way, the logic remains largely the same,
 since it lives in the abstract base class for both separate implementations, while the specifics are adapted, like
 for example the configuration command parameter names or when/if a function gets called on the handle.

```php
if ('Windows' === \PHP_OS_FAMILY) {
    // ...
```

## A practical use-case

The following code is taken from [the examples](https://github.com/machinateur/phpduino#example)
 that are part of the repository. It requires an Arduino with USB serial support.

### Installation

Using [composer](https://getcomposer.org), the library can easily be pulled in.

```
composer require machinateur/phpduino
```

### Arduino sketch

Make sure the Arduino (or other microcontroller device) is connected via USB and install the following sketch.

```c
byte incomingByte = 0;

void setup()
{
    Serial.begin(9600, SERIAL_8N1);
}

void loop()
{
  if (Serial.available() > 0) {
    incomingByte = Serial.read();

    Serial.print((char)incomingByte);
  }
}
```

It simply echoes back, byte-by-byte, what was previously sent to it. It obviously has the drawback of a limited
 buffer-size, but for such a simple and short amount of data, it's going to be OK.

### PHP script

The settings at the top are probably different for your own device, so check those before.

```php
// Example program for "echo.ino" communication to/from Arduino via USB serial.

use Machinateur\Arduino\streamWrapper;

require_once __DIR__ . '/../../vendor/autoload.php';

// Register the protocol and stream wrapper.
streamWrapper::register();

// This was tested on a Mac.
if ('Darwin' === \PHP_OS_FAMILY) {
    $deviceName = 'tty.usbmodem2101';
    $deviceName = 'cu.usbmodem2101';
} elseif ('Windows' === \PHP_OS_FAMILY) {
    // Also tested on Windows.
    $deviceName = 'COM7';
} else {
    $deviceName = '';

    \trigger_error(
        \sprintf('No device name specified in "%s" on line "%d"!', __FILE__, __LINE__ - 3), \E_USER_ERROR);
}

$deviceBaudRate = 9600;
$deviceParity   =   -1;
$deviceDataSize =    8;
$deviceStopSize =    1;

// Open the connection. Make sure the device is connected under the configured device name
//  and the `echo.ino` program is running.
$fd = \fopen("arduino://{$deviceName}", 'r+b', false);

$input  = 'hello world';
$output = '';

\stream_set_chunk_size($fd, 1);
\fwrite($fd, $input);

do {
    $output .= \fread($fd, 1);

    // Comment in the following line to see the progress byte by byte.
    //echo $output.\PHP_EOL;
} while ($output !== $input);

echo $output,
    \PHP_EOL,
    \PHP_EOL;
```

As you can see I've tried to comment everything that's happening in here.

## On general compatibility

I only have access to a limited amount of devices to test with. I've tweaked the inner workings of the library based on
 a 2022 Arduino UNO R3. On the OS side, I used Windows 10 Pro and MacOS 14 Sonoma.

It should also work fine with pretty much any USB device, as long as the port configuration matches.

## The GitHub Repository

For any questions, feedback or bug reports,
 please turn to the [issues over at GitHub](https://github.com/machinateur/phpduino/issues).
