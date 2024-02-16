const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const browserSync = require('browser-sync');

gulp.task('default', function (done) {
  style();
  done();
});

function style() {
  return gulp.src('./scss/style.scss')
      .pipe(sass().on('error', sass.logError))
      .pipe(gulp.dest('./css'))
      .pipe(browserSync.stream());
}

function watch() {
  browserSync.init({
    open: 'false',
    host: 'local.intranetsinbad24.com',
    proxy: 'local.intranetsinbad24.com', // BrowserSync proxy. Change as needed for your local dev but do not commit these changes.
  });

  gulp.watch('./scss/**/*.scss', style);
  gulp.watch('js/*.js').on('change', browserSync.reload);
  gulp.watch('templates/**/*.twig').on('change', browserSync.reload);
}

exports.watch = watch;