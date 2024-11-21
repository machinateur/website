# Using symfony front-controller to switch between applications

Have you ever been in a situation, where a completely separate test environment was impractical to integrate?
 I recently faced an interesting use-case for the symfony front-controller, which I thought was worth sharing
 (the technical details are altered here, for data protection reasons).

## Separation of concerns

In the ideal world, we try to draw a clear line between the production and testing/staging systems
 of a given software landscape.

This has proven to be a good pattern (as long as the secondary environment is nearly identical).

The main goals here are separating **data** and **program** of these different environments.
 Testing data from real data, made up information or placeholders from actual customer data.

## The exception to the rule

There are situations, where it's impossible to uphold this separation in both of these areas.
 This can have a variety of reasons.

Sometimes the opposite site of a system is built in a way that forces a new or dependent component
 to behave in a similar way, or boundaries impact the technical side of things. And exactly this was the
 case in my situation. And there are many other reasons or constellations that might require practical improvisation.

## The problem

The particular limitation was related to the domain of the URL.
 And for websites the domain name being used is a pretty big deal, right?
And nobody wants users to scroll around their testing data set.

Usually a production websites above particular sizes run nearly identical instances of some sort,
 which serve testing and development purposes.
Those systems are usually inaccessible to the public, but for developers, designers, testers.

Now, it's really hard to have both, your _production and testing environment_, running on the same domain.
 Set aside the usability implications, it's not straightforward to separate them in a bearable way.

And while adding a wrapper for doctrine's connection class to switch based on request value,
 as [described here](https://stackoverflow.com/a/71665884/2557685), would certainly keep the data storage separated
 enough, it would also come with the drawback that production and testing environment would always have to have the same
 code-base, i.e. are forced to the same _version_. This is not ideal, as most of the time code changes should be tested
 before brought to production.

But I became aware of another concept in symfony, called the **front-controller**. More on that shortly.

## The idea

It became clear to me after some time, such a separation would be possible using custom headers.

My header would be called `X-App-Environment` and it was to accept `prod` and `test` as value.

If given the choice, I would've relied on web-server configuration,
 as [supported by `ModRewrite`](https://httpd.apache.org/docs/2.4/mod/mod_rewrite.html#rewritecond).

<div class="border border-1 border-dark radius-1 rounded p-3 my-3">
  <p>Syntax: <code>RewriteCond {TestString} {CondPattern} [flags]</code></p>

  <p>
    \4. <code>%{HTTP:header}</code>, where header can be any HTTP MIME-header name,
      can always be used to obtain the value of a header sent in the HTTP request.
       Example: <code>%{HTTP:Proxy-Connection}</code> is the value of the HTTP header <code>Proxy-Connection:</code>.
      If an HTTP header is used in a condition this header is added to the Vary header of the response
       in case the condition evaluates to true for the request. It is not added if the condition evaluates to false
       for the request. Adding the HTTP header to the Vary header of the response is needed for proper caching.
      It has to be kept in mind that conditions follow a short circuit logic in the case of the `ornext|OR` flag
       so that certain conditions might not be evaluated at all.
  </p>
</div>

So something like the follow condition would probably work (I haven't tested it, though).

```
RewriteCond "%{HTTP:X-App-Environment}" "prod|test"
# ...
```

Sadly, it was also no option to adapt server configuration, as is often the case with managed hosting providers.
 So the solution also had to take that into consideration.

---

Further on I will leave out details such as permission settings for the sake of simplicity here.
 This is merely a simplified example to convey the concept,
 which may easily be adapted for other hosting structures or requirements.

Basically now, the idea is this...

Two identical installations of a symfony application (`/var/www/app/`, `/var/www/app-test/`), placed next to each other
 and next to the web-root (`/var/www/html/`) of a server.

Then, the web-root `/var/www/html/` as well as every environment's `public/` directory will contain an `index.php` file.

This is the **[front-controller](https://en.wikipedia.org/wiki/Front_controller)**,
 a [pattern used by symfony](https://symfony.com/doc/6.4/configuration/front_controllers_and_kernel.html#the-front-controller),
 and this is also what will help with implementing the solution: _A custom front-controller_.

So essentially a `index.php` file, to take on all requests and decide
 which of the available environments will be allowed to handle the request.

Here's an overview of the directory tree, omitting the unimportant bits:

```
/
  var/
    www/
      html/
        index.php       # <-- the custom front-controller to handle routing
      app/
        ...
        public/
          index.php     # <-- the prod instance's front-controller
        ...
      app-test/
        ...
        public/
          index.php     # <-- the test instance's front-controller
        ...
```

This structure has downsides for the hosting of frontend resources, which have to be accounted for in the templates.
 This is also something that should be possible to handle with different asset configurations, if such are used.
In my example, the frontend is not of importance (which is sadly not the case in most real-world situations).

## The code

The logic to decide which environment to call is simple, yet, working this close to the filesystem and in direct contact
 to user input, i.e. the header value in the request, it has to be absolutely temper proof. This means, it must not be
 possible to input path fragments, like `../../secret.key`, and those are passed through without proper sanitization.

Here's the commented solution I came up with.

```php
<?php

$system     = \strtolower($_SERVER['HTTP_X_APP_ENVIRONMENT'] ?? 'prod');
$projectDir = \dirname(__DIR__); // i.e. '/var/www'

// Whitelist of environments here, in hierarchy order. Do not use `$system` in function calls without sanitization!
foreach (['prod', 'test'] as $environment) {
    // Only pass for the selected target system.
    if ($system !== $environment) {
        continue;
    }

    // If `prod` environment, set the default path (no suffix), else use suffix.
    $environment = ('prod' === $environment) ? 'app' : \sprintf('app-%s', $environment);

    // Build the target environment path to the front-controller and include it, if it exists.
    $frontControllerPath = \sprintf('%s/%s/public/index.php', $projectDir, $environment);
    if (\is_file($frontControllerPath) && (__FILE__ !== $frontControllerPath)) {
        // Set up new parameters. Adapt as required by downstream application.
        $_SERVER['SCRIPT_FILENAME'] = $frontControllerPath;

        // Configure the symfony runtime of downstream applications.
        $_SERVER['APP_RUNTIME']         = ($_SERVER['APP_RUNTIME'] ?? $_ENV['APP_RUNTIME'] ?? 'Symfony\\Component\\Runtime\\SymfonyRuntime');
        $_SERVER['APP_RUNTIME_OPTIONS'] = [
            'project_dir' => \dirname($frontControllerPath, 2),
        ] + ($_SERVER['APP_RUNTIME_OPTIONS'] ?? $_ENV['APP_RUNTIME_OPTIONS'] ?? []);

        return require $frontControllerPath;
    }
}

// Respond with "Bad gateway" status, when the environment was not recognized.
\http_response_code(502);
exit 1;
```

All requests will go through here, and either call through to the application placed in `/var/www/app` or,
 if the `X-App-Environment` header is set to `test`, route to `/var/www/app-test`.

It is possible to add further environments to the chain here, by adding new entries to the loop header's array.

## Next steps

Such a specialized and nested structure of combined environments can and likely will require special logic and handling
 of edge-cases inside the application. Typical candidates are session-related things like flash-messages, cookies and
 so on.

Also, when there's a frontend involved, it should be made compatible with the new structure.

It can also make sense to add a env-var to the `.env` file and injected it to the `services.yaml` parameters,
 to indicate reliably to the application that it indeed _is_ a test instance or in return, it's the main instance.

Security should also be considered, as only the main instance should be openly available,
 in case the secondary system is indeed a staging system. Since each environment is a completely encapsulated
 application of its own, this can be done with the usual configuration.

IMO it's worth considering combining this technique
 with custom [symfony environments](https://symfony.com/doc/6.4/configuration.html#creating-a-new-environment)
 for better control over the configuration.

---

Let me know what you think!
