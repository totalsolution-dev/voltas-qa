(() => {

    'use strict';

    /**************** gulpfile.js configuration ****************/

    const

    // development or production
        devBuild = false,

        // directory locations
        dir = {
            src: '_vds-src/',
            build: 'public_html/assets/'
        },

        // modules
        gulp = require('gulp'),
        noop = require('gulp-noop'),
        newer = require('gulp-newer'),
        size = require('gulp-size'),
        imagemin = require('gulp-imagemin'),
        sass = require('gulp-sass'),
        postcss = require('gulp-postcss'),
        sourcemaps = devBuild ? require('gulp-sourcemaps') : null,
        browsersync = devBuild ? require('browser-sync').create() : null,
        htmlclean = require('gulp-htmlclean');
    //   print = require('gulp-print').default;

    console.log('Gulp', devBuild ? 'development' : 'production', 'build');



    const { task } = require('gulp');

    /**************** images task ****************/
    const imgConfig = {
        src: dir.src + 'images/**/*',
        build: dir.build + 'img/placeholder/',
        minOpts: {
            optimizationLevel: 5
        }
    };

    function images() {

        return gulp.src(imgConfig.src)
            .pipe(newer(imgConfig.build))
            .pipe(imagemin(imgConfig.minOpts))
            .pipe(size({ showFiles: true }))
            .pipe(gulp.dest(imgConfig.build));

    }
    exports.images = images;




    /**************** CSS task ****************/
    const cssConfig = {

        src: dir.src + 'scss/vds-main.scss',
        watch: dir.src + 'scss/**/*',
        build: dir.build + 'css/',
        sassOpts: {
            //sourceMap       : devBuild,
            imagePath: '/images/',
            precision: 3,
            errLogToConsole: true
        },

        postCSS: [
            // require('usedcss')({
            //     html: ['index.html']
            // }),
            // require('postcss-assets')({
            //     loadPaths: ['images/'],
            //     basePath: dir.build
            // }),
            require('autoprefixer')({
                browsers: ['> 1%']
            }), require('cssnano')
        ]
    };

    function css() {
        console.log('::::::: START: GULP / CSS  :::::::');
        // console.log('Config: --------------------------');
        // console.log(cssConfig);
        return gulp.src(cssConfig.src)
            .pipe(sourcemaps ? sourcemaps.init() : noop())
            .pipe(sass(cssConfig.sassOpts).on('error', sass.logError))
            .pipe(postcss(cssConfig.postCSS))
            .pipe(sourcemaps ? sourcemaps.write() : noop())
            .pipe(size({ showFiles: true }))
            .pipe(gulp.dest(cssConfig.build))
            .pipe(browsersync ? browsersync.reload({ stream: true }) : noop());

    }
    exports.css = gulp.series(css);
    task(css);

    // JS processing
    function js() {
        console.log('::::::: START: GULP / JS  :::::::');
        const out = dir.build + 'js/';
        console.log('output folder: ' + out);

        return gulp.src(dir.src + 'js/**/*')
            .pipe(newer(out))
            .pipe(size({ showFiles: true }))
            .pipe(gulp.dest(out));
    }
    // exports.js = gulp.series();
    task(js);

    // HTML processing
    function html() {
        console.log('::::::: START: GULP / HTML  :::::::');
        const out = 'EE_system/user/templates/voltas/';
        console.log('output folder: ' + out);

        return gulp.src(dir.src + 'components/**/*')
            .pipe(newer(out))
            // .pipe(htmlclean())
            .pipe(size({ showFiles: true }))
            .pipe(devBuild ? noop() : htmlclean())
            .pipe(gulp.dest(out))
    }
    // exports.html = gulp.series(css, html, js);
    task(html);


    /**************** server task (private) ****************/
    const syncConfig = {
        server: {
            baseDir: './build/',
            index: 'index.html'
        },
        port: 8000,
        open: false
    };

    // browser-sync
    function server(done) {
        if (browsersync) browsersync.init(syncConfig);
        done();
    }

    /**************** watch task ****************/
    function watch(done) {

        // image changes
        gulp.watch(imgConfig.src, images);

        // CSS changes
        gulp.watch(cssConfig.watch, css);
        //  js
        gulp.watch(dir.src + 'js/**/*', js);
        // html
        gulp.watch(dir.src + 'components/**/*', html);
        // gulp.watch(dir.build);
        console.log('::::::: START: GULP / WATCHER RUNNING  :::::::');
        done();
    }
    task(watch);
    /**************** default task ****************/
    // exports.default = gulp.series(exports.css, watch, server);                 
    exports.default = gulp.series(html, css, js, watch);

})();