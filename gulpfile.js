(() => {

    'use strict';
  
    /**************** gulpfile.js configuration ****************/
  
    const
  
      // development or production
      devBuild  = ((process.env.NODE_ENV || 'development').trim().toLowerCase() === 'development'),
  
      // directory locations
      dir = {
        src         : '_vds-src/',
        build       : 'public_html/themes/user/site/default/asset/'
      },

      // modules
      gulp          = require('gulp'),
      noop          = require('gulp-noop'),
      newer         = require('gulp-newer'),
      size          = require('gulp-size'),
      imagemin      = require('gulp-imagemin'),
      sass          = require('gulp-sass'),
      postcss       = require('gulp-postcss'),
      sourcemaps    = devBuild ? require('gulp-sourcemaps') : null,
      browsersync   = devBuild ? require('browser-sync').create() : null,
      htmlclean     = require('gulp-htmlclean');
  
    console.log('Gulp', devBuild ? 'development' : 'production', 'build');





    /**************** images task ****************/
    const imgConfig = {
        src           : dir.src + 'images/**/*',
        build         : dir.build + 'img/placeholder/',
        minOpts: {
        optimizationLevel: 5
        }
    };
    
    function images() {
    
        return gulp.src(imgConfig.src)
        .pipe(newer(imgConfig.build))
        .pipe(imagemin(imgConfig.minOpts))
        .pipe(size({ showFiles:true }))
        .pipe(gulp.dest(imgConfig.build));
    
    }
    exports.images = images;   




    /**************** CSS task ****************/
    const cssConfig = {

        src         : dir.src + 'scss/vds-main.scss',
        watch       : dir.src + 'scss/**/*',
        build       : dir.build + 'css/',
        sassOpts: {
        //sourceMap       : devBuild,
        imagePath       : '/images/',
        precision       : 3,
        errLogToConsole : true
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
        })
        ,require('cssnano')
        ]
    };
    
    function css() {
    
        return gulp.src(cssConfig.src)
        //.pipe(sourcemaps ? sourcemaps.init() : noop())
        .pipe(sass(cssConfig.sassOpts).on('error', sass.logError))
        .pipe(postcss(cssConfig.postCSS))
        //.pipe(sourcemaps ? sourcemaps.write() : noop())
        .pipe(size({ showFiles: true }))
        .pipe(gulp.dest(cssConfig.build))
        .pipe(browsersync ? browsersync.reload({ stream: true }) : noop());
    
    }
    exports.css = gulp.series(images, css);

    // HTML processing
    function html() {
        const out = 'EE_system/user/templates/voltas/_partials';
        
        return gulp.src(dir.src + 'components/**/*')
        .pipe(newer(out))
        // .pipe(htmlclean())
        .pipe(devBuild ? noop() : htmlclean())
        .pipe(gulp.dest(out));
    }
    exports.html = gulp.series(css, html);



    /**************** server task (private) ****************/
    const syncConfig = {
        server: {
        baseDir   : './build/',
        index     : 'index.html'
        },
        port        : 8000,
        open        : false
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
        gulp.watch(dir.build);
        
        gulp.watch(dir.src + 'components/**/*', html);
        done();
    
    }
    
    /**************** default task ****************/
    // exports.default = gulp.series(exports.css, watch, server);                 
    exports.default = gulp.series(exports.html, watch);                 
  
  })();