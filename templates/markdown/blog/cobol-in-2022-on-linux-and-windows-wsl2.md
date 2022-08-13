Checking out COBOL in 2022 on Linux and on Windows with WSL2. Let me say this first: **This is no in-depth walkthrough
of features or syntax or style of COBOL, but a guide on how to get started, setting it up, learning and using it.**

## What is COBOL {#what-is-cobol .display-3}

COBOL[^1] - or "**CO**mmon **B**usiness-**O**riented **L**anguage" - is a compiled English-like computer programming
language, primarily for business use. It's imperative, procedural and now (for 20 years) object-oriented.

Today it is used mostly (and still actively developed with) by financial institutes and states. It was widely used for
mainframe appliances.

Designed in 1959 as a portable programming language for data processing, it soon became widely adopted, thanks to the
Department of Defense forcing everyone to support it. The idea was for the language to be self-documenting, hence its
English-like syntax (dots everywhere!). But with over 300 reserved words, and being criticized for that verbosity and
among other points the bad support for structured programming, it doesn't really hit the mark. Also, there is pretty
much no standard library.

[Says Wikipedia](https://en.wikipedia.org/wiki/COBOL)

The last part is kind of my opinion on this language. I find it very hard to grasp, in face of the unbelievably
extensive vocabulary this language possesses. But in its own way that makes it more interesting to study.

[^1]: **CO**mmon **B**usiness-**O**riented **L**anguage

### What is GnuCOBOL {#what-is-gnucobol}

GnuCOBOL is a free implementation of the COBOL programming language. It's well-maintained and supports a wide variety of
dialects and features.

## The Result {#the-result .display-3}

What we will get at the end of this article is the following COBOL program (and output). Yep, that's right, it's only
`9` lines of code, but buckle up, we'll have to build GnuCOBOL from source!

![The finished Program in the Editor](/res/image/blog/cobol-in-2022-on-linux-and-windows-wsl2/code-editor-and-shell.jpeg)

## The "*Why*" {#the-why .display-3}

I became a bit interested in COBOL, when I first heard of it. It was said to run "all that big financial software", so
naturally I wanted to know what that looked like. For some time I played with the thought of trying it out but
ultimately found the work involved, then, not justified. I substantially lacked knowledge in Linux stuff as well as the
technology in terms of Windows-to-Linux interoperability.

![Google Image Search for "COBOL"](/res/image/blog/cobol-in-2022-on-linux-and-windows-wsl2/code-google-image-search.jpeg)

And well, maybe you've seen some screenshots of COBOL code on Google image search and now want to cry. Maybe. But wait,
actually, the syntax is not as bad as imagery might suggest.

It's astonishing to see how simple (and short) some solutions are, where other languages would've required the
programmer to write a-lot more code. It makes me think about the possible advantages and disadvantages that a more
"focused" or "specialized" approach, like a custom DSL[^2] for example, as foundation for business processes might
provide.

[^2]: **D**omain-**S**pecific **L**anguage

### Short Rambling About GPL/DSL {#short-rambling-gpl-dsl}

But it has to be kept in mind, such a language is not designed to solve larger-scale problems, like it could be done
with a GPL[^3]. COBOL was initially created as DSL, but developed further as GPL. It still remains roughly in the same
area of application (processing data inputs/outputs). It remains specialized.

In an age of well known general purpose programing languages (C, Python, PHP and the like) I found myself, as a user of
those languages, often enough questioning the language in favour of another (another GPL thought), but never in favour
of a totally specific, custom language. And to be honest gradle made me fear DSLs, big time.

So if nothing else, COBOL promises for some fun exploring its syntax and features.

[^3]: **G**eneral-**P**urpose **L**anguage

## Set-up {#set-up .display-3}

Ok, so if you really want to go through with this (I hope so) follow these steps:

> Keep in mind I'm describing the procedure here based on a system running Windows 10 Pro with WSL2 Ubuntu 20.04 LTS. To
> what extent and to what kind of varying degree these instructions apply to other environments, I can't say.
>
> Should be the same procedure as for Ubuntu when booted natively.

### 1. Install WSL2 Ubuntu {#1-install-wsl2-ubuntu}

Best starting point is to get to know WSL, WSL2 specifically. That's WSL, as in ["**W**indows **S**ubsystem for
**L**inux"](https://docs.microsoft.com/en-us/windows/wsl/install). Go ahead and install it, if it isn't already.

On an up-to-date system, this should be a matter of some minutes with the commands `wsl --install --d ubuntu`
and `wsdl --set-default-version 2`. Skip this step if you're running ubuntu as your main OS.

### 2. Install GnuCOBOL Source {#2-install-gnucobol-src}

We'll obtain a copy of the GnuCOBOL release (the latest stable at this date).

Open a WSL shell to your `$HOME` directory. Then type the commands.

```bash
mkdir -p "~/software"
cd "~/software"

wget "https://sourceforge.net/projects/gnucobol/files/gnucobol/3.1/gnucobol-3.1.2.tar.gz/download" "gnucobol-3.1.2.tar.gz"
tar -xvf "gnucobol-3.1.2.tar.gz"

cd "gnucobol-3.1.2"
```

This will create a new `software` directory in the user home directory, download and unpack the `tar`-archive. Then
enter the directory.

### 3. Install system/library dependencies {#3-install-sys-lib-deps}

It's necessary to install some system-level dependencies for the build. Some are tools, some are libraries, needed for
optional modules of the GnuCOBOL build (Berkley DB, Screen I/O, XML & JSON I/O).

First the essentials for doing our own builds.

```bash
sudo apt-get update
sudo apt-get install build-essential make git
```

Then the libraries (source, e.g. `-dev`)...

```bash
sudo apt-get install libdb-dev libgmp3-dev libncurses5-dev libncursesw5-dev libxml2-dev libcjson-dev
```

### 4. Run the build process {#4-run-build-process}

First configure the environment for the build.

```bash
./configure
```

The output (at the end) should be something close to the following:

```
configure: GnuCOBOL Configuration:
configure:  CC                gcc
configure:  CFLAGS            -O2 -pipe -finline-functions -fsigned-char -Wall -Wwrite-strings -Wmissing-prototypes -Wno-format-y2k
configure:  LDFLAGS            -Wl,-z,relro,-z,now,-O1
configure:  COB_CC            gcc
configure:  COB_CFLAGS         -pipe -I/usr/local/include -Wno-unused -fsigned-char -Wno-pointer-sign
configure:  COB_LDFLAGS
configure:  COB_DEBUG_FLAGS   -ggdb3 -fasynchronous-unwind-tables
configure:  COB_LIBS          -L${exec_prefix}/lib -lcob -lm
configure:  COB_CONFIG_DIR    ${datarootdir}/gnucobol/config
configure:  COB_COPY_DIR      ${datarootdir}/gnucobol/copy
configure:  COB_LIBRARY_PATH  ${exec_prefix}/lib/gnucobol
configure:  COB_OBJECT_EXT    o
configure:  COB_MODULE_EXT    so
configure:  COB_EXE_EXT
configure:  COB_SHARED_OPT    -shared
configure:  COB_PIC_FLAGS     -fPIC -DPIC
configure:  COB_EXPORT_DYN    -Wl,--export-dynamic
configure:  COB_STRIP_CMD     strip --strip-unneeded
configure:  Dynamic loading:                             System
configure:  Use gettext for international messages:      yes
configure:  Use fcntl for file locking:                  yes
configure:  Use math multiple precision library:         gmp
configure:  Use curses library for screen I/O:           ncursesw
configure:  Use Berkeley DB for INDEXED I/O:             yes
configure:  Used for XML I/O:                            libxml2
configure:  Used for JSON I/O:                           cjson
```

Then run the build process, like this...

```bash
make

# very important to make sure everything works fine, around 700 internal tests
make check
# run against a NIST COBOL-85 test suite
make test

sudo make install
# this will likely print some warning about a symbolic link
sudo ldconfig
```

### 5. Add environment variables {#5-add-env-vars}

In `$HOME/.profile`, append the following snippet, to be able to always call `cobc`/`cobcrun` in all future sessions.

```bash
# ...

# set up cobol custom build
SW_GNUCOBOL_PATH="$HOME/software/gnucobol-3.1.2"
LD_LIBRARY_PATH="$LD_LIBRARY_PATH:$SW_GNUCOBOL_PATH/lib"
PATH="$PATH:$SW_GNUCOBOL_PATH/bin"
```

So that's it, we're all set and done. Restart the shell (or set the environment variables in the current session).

## Hop In and Run {#hop-in-and-run .display-3}

Alright, let's get to the code.

First, choose an editor. Because it was the easiest for me, I went with VS Code with the "COBOL" Extension by "BitLang"
installed. You are, of course, free to use `vi`, or whatever you like best.

```bash
touch ./hello-world.cob
```

Create a new file, best in a location accessible from WSL2 (if you're using that). There'll be some rulers denoting the
"**FIXED**" format indentation. Thankfully we will not need to honor this, when setting the format to "**FREE**".

![The empty VS Code Editor](/res/image/blog/cobol-in-2022-on-linux-and-windows-wsl2/code-editor-empty.jpeg)

We can do so, either using the inline directive at the top of the file or using compiler options when compiling the
program using `cobc -free`. Then put the essential sections of the COBOL program into place. The resulting code is the
following:

```cobol
>> SOURCE FORMAT IS FREE
*> GnuCOBOL - "Hello world" example
IDENTIFICATION DIVIDISION.
PROGRAM-ID. hello-world.
AUTHOR. your-name-here.
PROCEDURE DIVISION.
DISPLAY "Hello world"
STOP RUN.

```

To then compile the program, open the directory in the terminal (WSL2) and run the following command:

```bash
cobc -x ./hello-world.cob
```

Which will output a new file `hello-world` (binary) to the directory. This file can now be executed. It will
print `Hello world`.

![Shell Running the Program](/res/image/blog/cobol-in-2022-on-linux-and-windows-wsl2/shell-run-program.jpeg)

## More Resources {#more-resources .display-3}

- [My COBOL Playground Repository](https://github.com/machinateur/cobol-playground)
- [The GnuCOBOL FAQ](https://gnucobol.sourceforge.io/faq/index.html#building-gnucobol-3-0-release-candidates)
- [Derek Banas' great Single-Video Crash-Course to COBOL](https://www.youtube.com/watch?v=TBs7HXI76yU)
