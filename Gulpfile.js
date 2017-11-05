'use strict';

var gulp    = require('gulp');
var sass    = require('gulp-sass');
var concat  = require('gulp-concat');
var uglify  = require('gulp-uglify');

var dir = {
    assets: './src/AppBundle/Resources/',
    dist: './web/',
    npm: './node_modules/'
};

gulp.task('sass', function() {
    gulp.src(dir.assets + 'style/main.scss')
        .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
        .pipe(concat('style.css'))
        .pipe(gulp.dest(dir.dist + 'css'));
});

gulp.task('scripts', function() {
    gulp.src([
            // Third party assets
            dir.npm + 'jquery/dist/jquery.min.js',
            dir.npm + 'bootstrap-sass/assets/javascripts/bootstrap.min.js',

            // Main JS file
            dir.assets + 'scripts/main.js'
        ])
        .pipe(concat('script.js'))
        .pipe(uglify())
        .pipe(gulp.dest(dir.dist + 'js'));
});

gulp.task('images', function() {
    gulp.src([
            dir.assets + 'images/**'
        ])
        .pipe(gulp.dest(dir.dist + 'images'));
});

gulp.task('fonts', function() {
    gulp.src([
        dir.npm + 'bootstrap-sass/assets/fonts/**',
        dir.npm + 'font-awesome-sass/assets/fonts/**'
        ])
        .pipe(gulp.dest(dir.dist + 'fonts'));
});

// Initial copy
// gulp.task('copy', ['fonts', 'scripts', 'sass', 'images']);

// Copy and watch
// gulp.task('watch', function() {
//    gulp.watch(dir.assets + 'scripts/**/*.js', ['scripts']);
//    gulp.watch(dir.assets + 'style/**/*.scss', ['sass']);
//    gulp.watch(dir.assets + 'images/**', ['images']);
// });

// Run all tasks
// gulp.task('default', ['copy', 'watch']);

gulp.task('default', ['fonts', 'scripts', 'sass', 'images']);