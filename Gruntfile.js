module.exports = function(grunt) {
"use strict";

    grunt.initConfig({

        // gets the package vars
        pkg: grunt.file.readJSON("package.json"),
        svn_settings: {
            path: "../../../../wp_plugins/<%= pkg.name %>",
            tag: "<%= svn_settings.path %>/tags/<%= pkg.version %>",
            trunk: "<%= svn_settings.path %>/trunk",
            exclude: [
                ".git/",
                ".gitignore",
                ".sass-cache/",
                "node_modules/",
                "assets/sass/",
                "assets/js/admin.js",
                "assets/js/checkout.js",
                "PSDs",
                "Gruntfile.js",
                "README.md",
                "package.json",
                "config.rb",
                "*.zip"
            ]
        },

        // javascript linting with jshint
        jshint: {
            options: {
                "bitwise": true,
                "eqeqeq": true,
                "eqnull": true,
                "immed": true,
                "newcap": true,
                "es5": true,
                "esnext": true,
                "latedef": true,
                "noarg": true,
                "node": true,
                "undef": false,
                "browser": true,
                "trailing": true,
                "jquery": true,
                "curly": true
            },
            all: [
                "Gruntfile.js",
                "assets/js/admin.js",
                "assets/js/checkout.js"
            ]
        },

        // uglify to concat and minify
        uglify: {
            dist: {
                files: {
                    "assets/js/admin.min.js": ["assets/js/admin.js"],
                    "assets/js/checkout.min.js": ["assets/js/checkout.js"]
                }
            }
        },

        // compass and scss
        compass: {
            dist: {
                options: {
                    config: "config.rb",
                    force: true,
                    outputStyle: "compressed"
                }
            }
        },

        // watch for changes and trigger compass
        watch: {
            compass: {
                files: [
                    "assets/sass/**"
                ],
                tasks: ["compass"]
            },
            js: {
                files: [
                    "<%= jshint.all %>"
                ],
                tasks: ["jshint", "uglify"]
            }
        },

        // rsync commands used to take the files to svn repository
        rsync: {
            tag: {
                src: "./",
                dest: "<%= svn_settings.tag %>",
                recursive: true,
                exclude: "<%= svn_settings.exclude %>"
            },
            trunk: {
                src: "./",
                dest: "<%= svn_settings.trunk %>",
                recursive: true,
                exclude: "<%= svn_settings.exclude %>"
            }
        },

        // shell command to commit the new version of the plugin
        shell: {
            svn_add: {
                command: 'svn add --force * --auto-props --parents --depth infinity -q',
                options: {
                    stdout: true,
                    stderr: true,
                    execOptions: {
                        cwd: "<%= svn_settings.path %>"
                    }
                }
            },
            svn_commit: {
                command: "svn commit -m 'updated the plugin version to <%= pkg.version %>'",
                options: {
                    stdout: true,
                    stderr: true,
                    execOptions: {
                        cwd: "<%= svn_settings.path %>"
                    }
                }
            }
        }
    });

    // load tasks
    grunt.loadNpmTasks("grunt-contrib-watch");
    grunt.loadNpmTasks("grunt-contrib-jshint");
    grunt.loadNpmTasks("grunt-contrib-uglify");
    grunt.loadNpmTasks("grunt-contrib-compass");
    grunt.loadNpmTasks("grunt-rsync");
    grunt.loadNpmTasks("grunt-shell");

    // compile task
    grunt.registerTask("compile", [
        "jshint",
        "compass",
        "uglify"
    ]);

    // default task
    grunt.registerTask("default", [
        "compile",
        "rsync:tag",
        "rsync:trunk",
        "shell:svn_add",
        "shell:svn_commit"
    ]);
};
