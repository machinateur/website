# Mobile tab hoarders, behold: The ultimate Tab Transfer tool

Showcasing the latest release of tab-transfer:

> A tool to transfer Google Chrome tabs from your Android or iPhone to your computer.

See https://github.com/machinateur/tab-transfer/releases/tag/0.5.1 ([*](https://github.com/machinateur/tab-transfer/releases/tag/0.5.0))
 for more information.

<!-- raw -->
<div class="row"><div class="col-12 col-md-8 offset-0 offset-md-2"
    data-app-component="image-zoom">
    <p class="text-center">
    <img src="/res/image/blog/mobile-tab-hoarders-behold-the-ultimate-tab-transfer-tool/tab-transfer-terminal.png"
        alt="Mac terminal window of the tab-transfer command line interface" class="w-100" />
    </p>
</div></div>
<!-- endraw -->

---

<div class="toc">
</div>

## What is this?

So, `tab-transfer` is a command-line tool to transfer Google Chrome tabs from your phone to your computer using developer tools.

I would say, currently, this is the most reliable way to transfer large amounts of opened tabs when switching phones.

The main features include the following:

- Copy all open tabs from Chrome on Android and iOS to your computer.
- Reopen exported tabs on your new Android or iOS phone from your computer.
- Completely cross-platform! Works on Windows, Mac and Linux computers!
- Also supports Safari on iPhone!

In this blog article, I will be discussing a bit of history, motivation and technical stuff.

## The Backstory

A little less than two years ago, this started out as a little side project. Initially named `android-chrome-tab-transfer`,
 I wasn't expecting much of anyone to care about some random little script that I wrote as a quick and dirty workaround.
A workaround for the limitation, that has been put on me (and apparently also other people out there, who just can't let go of old browser tabs).

That limitation, explicitly stated is this:

> Whenever switching to a new phone (and some of us do more often than others),
>  I was unable to take all of my open tabs with me.
> And there are no free tools available that I could find.

So, as it happens, I had to make my own. I initially put it up on GitHub and went with it.

It only supported Android, as I used only that OS. At the time, there were no means available at Apple's anyway. Not much of a surprise, isn't it?

The underlying concept of this whole tool was (and still is) using *Chrome's Dev-Tools Protocol* to interact with the browser.
 This is not only possible locally on your computer, with some variant of Chrome, but also for mobile.
  On mobile, this is actually only a developer feature, to be able to debug web-pages on actual physical devices or inside an emulator.

With time some issues were opened on the repository. Since I was initially targeting a non-technical audience (myself), there were questions
 in regard to installing and using it correctly. But the software just wasn't meant to be operated by non-programmers.

I dragged addressing that matter out for far to long. Until recently.

## The `0.5.0` Update

> _Note: While writing this article, version [0.5.1](https://github.com/machinateur/tab-transfer/releases/tag/0.5.1) was released with some bugfixes._

Now, to tell the whole story: I already had figured out the way I wanted to change the script into a full application, roughly, for some time.

Then, about two years ago, I did a research spike for fun, to see and find if the technique I used for Android would be adaptable for iOS.
 At the time, it was possible for *Safari* only, and there even was a proxy software available by Google.
Of course this was also not a surprise. I created [a ticket for myself](https://github.com/machinateur/tab-transfer/issues/13)
 with some information and left it at that.

Then, only recently, I finally decided to cross this off of my TODO list. I got to work somewhat a week ago.
The rewrite came along far better than I had planned, so now this is a fully fledged CLI application,
 which can be used to transfer tabs *from* and *to* Android and iOS devices.
All that, and it still supports using the old legacy command, from the pre-`0.5.0` version.

The tabs are stored in JSON file formatting. The code is extendable quite easily. There are only some core concepts to know.

You can read the fully open-source code of this tool and all the software it depends on, [over on GitHub](https://github.com/machinateur/tab-transfer).

## The inner Workings

*All information as of time of writing.*

**Speaking of core concepts**: The `tab-transfer` application is a normal Symfony console application.

Apart from the [`console` component](https://symfony.com/doc/current/components/console.html), there are only
 the [`event-dispatcher`](https://symfony.com/doc/current/components/event_dispatcher.html) (for backwards compatibility with the legacy command),
 the [`filesystem`](https://symfony.com/doc/current/components/filesystem.html) and [`process`](https://symfony.com/doc/current/components/process.html).

I'll omit the namespace when below `\Machinateur\ChromeTabTransfer\`...

### Commands

The application provides the following commands:

```
tab-transfer 0.5.1

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display help for the given command. When no command is given display help for the list command
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  check-environment  Check environment for required dependencies ony your system. Implicitly executed before copying tabs.
  completion         Dump the shell completion script
  help               Display help for a command
  list               List commands
  reopen-tabs        Restore tabs to your phone's Chrome browser.
 copy-tabs
  copy-tabs:android  Transfer tabs from your Android's Chrome browser.
  copy-tabs:iphone   Transfer tabs from your iPhone's Chrome browser.
```

### Types of Commands

There are three types of commands in the whole application:

- `CheckEnvironment`
- `AbstractCopyTabsCommand`
- `ReopenTabs`

All their operations are based around only one of those types: `AbstractCopyTabsCommand`.
 Typically, any implementation of that blueprint would have a 1:1 relation to a corresponding driver.
As other commands, that can operate on different types of drivers, discover them only through the command they are associated with respectively,
 this is a hard requirement.

The driver can be created by the command based on console input.

The `CopyTabsFromAndroid` (`copy-tabs:android`) and `CopyTabsFromIphone` (`copy-tabs:iphone`) commands are examples for concrete
 implementations of the abstract command. All of these call their own environment-checking logic internally (through `CheckEnvironment`).
Finally, the `ReopenTabs` command is a combination of both: It can work with more than one driver, because its own driver wraps whatever it gets
 from the command line. And this is where the command discovery comes in, which simply scans all commands inside the `copy-tabs` namespace for
implementations of `AbstractCopyTabsCommand`. That mechanic is also hardcoded, because said base command prepends that prefix to the driver name.
 Details can be read in the code directly. It sounds more complicated than it actually is, thanks to Symfony's command library being very helpful
  wit this.

Next up: Drivers.

### Drivers

Any driver has to implement the `AbstracDriver`, to be returnable by a command. There's the `DriverInterface`, but all the functionalities
 come together in the abstract base class.

The `CopyTabsService` is able to run the driver through the default flow of its operation, that is:

- Start
- Transport tabs
  - *This means both directions are possible - download and upload*
- Stop

Each driver must return a `TabLoaderInterface` concrete for the correct operation, thereby separating driver start/stop logic from
 the actual operation that is executed in-between.

These concepts are also used by the `RestoreTabsDriver`, that's the aforementioned wrapping driver. It can restore tabs from a given file
 and send them to the attached device. It's exposed by the `repen-tabs` command, which is purposefully not part of the `copy-tabs` command
  namespace.

### Tab-Loaders

There are not only tab-loaders that are used for exporting/importing directly, but also those that help in those operations behind the scenes.

- `CurlReopenTabLoader`
- `CurlTabLoader`
- `JsonFileTabLoader`
- `JsonFileTabLoaderTrait`
- `WdpReopenTabLoader`

These are partly related to each other, re-use each other or are integrated as traits (behaviour style).

### File Templates

Each driver can choose to define file templates, that are `\Stringable` object representations of files that are to be written on disk.
 They are typically created from the array structure that represents the transferred tabs or tabs that were read from disk.

- `JsonFile`
- `MarkdownFile`
- `ReopenScriptFile`
- `WebsocketWdpClient`

## The `COMPATIBILITY_MODE`

This is the way to make the legacy `copy-tabs` command available.

See [the project README](https://github.com/machinateur/tab-transfer/tree/main?tab=readme-ov-file#legacy-command) for details.

## The ADB technique of Android

The _ADB_ technique is based on the *A*ndroid *D*ebug *B*ridge connection, and it's a first class developer feature for Android Devs.
 It allows connectivity to physical devices as well as emulated ones.

Find more details on it over at the [Android docs](https://developer.android.com/tools/adb). It supports a wide range of use-cases.
Here, the socket forwarding capabilities are utilized to connect TCP to a `localabstract` on the device. Different sockets correspond to
 different browser versions or flavours (for example Chrome's beta/canary channel).

For it to work, the USB debugging mode has to be active, which itself is a developer option on Android,
 so the developer mode has to be activated first.

On startup, first the ADB server is started, if not yet running. Then authentication is performed with the device connected.
 Sometimes the confirmation popup on the device only appears after re-connecting the cable.
If the wait time is not long enough for this to successfully confirm, pair and download with the `copy-tabs:android` command,
 check the `--wait` (`-w`) parameter to configure this.

When the driver stops the ADB tool is called again to remove the exposed TCP port for the device.
 There's an option to disable this cleanup, `--skip-cleanup`, which will also cause the bash reopen-script will not generate.
  It would risk reopening all tabs on the same device, which would not be desirable.

On Android, it's required to enable USB debugging in the developer settings.
 Here's [a guide on how to access those settings on your Android phone](https://developer.android.com/studio/debug/dev-options#Enable-debugging).

<!-- raw -->
<div class="row"><div class="col-12 col-md-8 col-xl-6 offset-0 offset-md-2 offset-xl-3"
    data-app-component="image-zoom">
    <p class="text-center">
        <img src="/res/image/blog/mobile-tab-hoarders-behold-the-ultimate-tab-transfer-tool/android-developer-usb-debugging.png"
            alt="The android settings overview for the USB debugging toggle" class="w-25" />
    </p>
</div></div>
<!-- endraw -->

Make sure to always disable the USB debugging capabilities after you're done using them. It could be a security risk to your device.

## The WDP client technique on iOS

On iOS, I had to get pretty creative to make this work.
 Gladly, there's the [`ios-webkit-debug-proxy` by Google](https://github.com/google/ios-webkit-debug-proxy), which makes this whole thing possible.

It translates the *C*hrome *D*ev-Tools *P*rotocol (_CDP_) to the *W*ebKit *D*ev-Tools *P*rotocol (_WDP_)
 used by Apple's WebKit version. Sadly, those are not fully compatible. You can see the subset that's usable here:

> [The `WebKit/WebKit` repository on GitHub](https://github.com/WebKit/WebKit/tree/0ba313f0755d90540c9c97a08e481c192f78c295/Source/JavaScriptCore/inspector/protocol)

Safari was supported for some time, but Chrome is only since iOS `16.4+` capable of exposing debugging capabilities through USB.

The main issue that iOS support posed, was that it does expose the endpoint for simply downloading all tabs as JSON list, but
 there was nowhere near the same way to open tabs. In fact, this route was noz (yet?)
  implemented [in the C code of the proxy](https://github.com/google/ios-webkit-debug-proxy/blob/cd122789563ab78c79d8c705aafe9a0ca7e4b872/src/ios_webkit_debug_proxy.c#L1074).

To work around that, I simply generate [a custom Dev-Tools client](https://github.com/machinateur/tab-transfer/blob/487cf6e601d9d47fd13e1e98a587229dd523c5a7/res/wdp_client.html)
 (called the WDP client), that's connecting to the actual dev-tools websocket connection,
 which is natively supported in the browser. The WDP client is a simple HTML file with some JavaScript code in it.

Currently, the [`Runtime.evaluate()` method](https://github.com/WebKit/WebKit/blob/0ba313f0755d90540c9c97a08e481c192f78c295/Source/JavaScriptCore/inspector/protocol/Runtime.json#L221)
 is sent to the initial tab, that's currently open on the device.
Therefor above command will itself be enclosed to the [`Target.sendMessageToTarget` method](https://github.com/WebKit/WebKit/blob/c73cb00c98f8d703a63b8826e2ee38ebcd506bfc/Source/JavaScriptCore/inspector/protocol/Target.json#L34),
 which is apparently one of the few methods mapped by the proxy.
I have not found any hints on what might be supported at all, but based on the examples for ios-webkit-debug-proxy,
 this was the next best solution that worked.

This implies, that if new-tab openings are blocked for the page, you will have to confirm and allow those first, before
 the opening process will show effect.
That's because the JavaScript command for the runtime to evaluate is a simple [`open()` call](https://developer.mozilla.org/en-US/docs/Web/API/Window/open).

This initial tab's target ID can be specified through the `--wdp-target`, for example:

```
tab-transfer reopen-tabs -i iphone --wdp-target page-1
```

This will result in the initial WDP command in the generated client file will be the following:

```json
    {
        "id": 1,
        "method": "Target.sendMessageToTarget",
        "params": {
            "targetId": "page-1",
            "message": "{\n    \"id\": 1,\n    \"method\": \"Runtime.evaluate\",\n    \"params\": {\n        \"expression\": \"open('chrome-native://newtab/');\"\n    }\n}"
        }
    }
```

This option is undocumented and only used for debugging purposes. Most of the time establishing the connection
 will result in the target ID being overwritten, because the proxy is built that way.

The proxy process will run in the background and stop with the driver. The browser will be launched and restoration
 progress is displayed.

<!-- raw -->
<div class="row"><div class="col-12 col-md-8 col-xl-6 offset-0 offset-md-2 offset-xl-3"
    data-app-component="image-zoom">
    <p class="text-center">
        <img src="/res/image/blog/mobile-tab-hoarders-behold-the-ultimate-tab-transfer-tool/tab-transfer-wdp-ui.png"
            alt="Chrome on Mac showing the user interface of the custom WDP client" class="w-100" />
    </p>
</div></div>
<!-- endraw -->

For iPhone it's required to have Safari and Chrome debugging over USB connection enabled.
 Here's [a guide on how to access those settings on your iPhone](https://developer.chrome.com/blog/debugging-chrome-on-ios/#getting_started).
Here's also [the official page by apple describing the steps](https://developer.apple.com/documentation/safari-developer-tools/inspecting-ios#Enabling-inspecting-your-device-from-a-connected-Mac).

<!-- raw -->
<div class="row"><div class="col-12 col-md-6 col-xl-4 offset-0 offset-xl-1"
    data-app-component="image-zoom">
    <p class="text-center">
        <img src="/res/image/blog/mobile-tab-hoarders-behold-the-ultimate-tab-transfer-tool/iphone-safari-web-inspector.png"
            alt="The android settings overview for the USB debugging toggle" class="w-25" />
    </p>
</div><div class="col-12 col-md-6 col-xl-4 offset-0 offset-xl-2"
    data-app-component="image-zoom">
    <p class="text-center">
        <img src="/res/image/blog/mobile-tab-hoarders-behold-the-ultimate-tab-transfer-tool/iphone-chrome-content-settings.png"
            alt="The android settings overview for the USB debugging toggle" class="w-25" />
    </p>
</div></div>
<!-- endraw -->

Make sure to always disable the USB debugging capabilities after you're done using them. It could be a security risk to your device.

## Windows installer

I was able to create a Windows installer package using [InnoSetup](https://github.com/jrsoftware/issrc).
 Since all software dependencies allow redistribution, I was able to add PHP, ADB and the WDP Proxy, along with the application itself as `.phar`.

See https://github.com/machinateur/tab-transfer/releases/tag/0.5.1 ([*](https://github.com/machinateur/tab-transfer/releases/tag/0.5.1))
 for more information. You can download the installer `.exe` there.

The repository contains scripts and other information to ease the process of compiling the setup.

<!-- raw -->
<div class="row"><div class="col-12 col-md-8 col-xl-6 offset-0 offset-md-2 offset-xl-3"
    data-app-component="image-zoom">
    <p class="text-center">
        <img src="/res/image/blog/mobile-tab-hoarders-behold-the-ultimate-tab-transfer-tool/tab-transfer-installer.png"
            alt="The first screen of the tab-transfer Windows installer" class="w-100" />
    </p>
</div></div>
<!-- endraw -->

## Why explain and read all this?

Well that's up to you of course.

**The intent behind this is to make the application more useful to you, because it can be extended and adpted to you needs.**

For example, it would be perfectly possible to add a new file format to export from the tabs, if you need it.
 Sure, with that example, you could use something  like `jq` to transform the JSON data.

Another cool thing to see in the future is which possibilities this can offer for the desktop browsers as well.

> Who knows what one can make out of something so small as an idea?

Browse the code on GitHub!
 If you like it, and it was useful, you please consider leaving a star. :)

> https://github.com/machinateur/tab-transfer

---

Have a great day, and let me know what you think below in the comments!
