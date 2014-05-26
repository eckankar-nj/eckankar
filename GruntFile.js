module.exports = function(grunt) {
  var _ = grunt.util._
  var pkg = grunt.file.readJSON('package.json')

  grunt.initConfig({
    pkg: pkg,
    jshint: {
      all: ['./'],
      dir: ['*.js'],
      sub: ['*/'],
      options: _.extend({
        ignores: ['**/**/node_modules/', '**/**/vendor/', '**/**.min.js']
      }, pkg.jshintConfig)
    },
    less: {
      options: {
        cleancss: true
      },
      build: {
        files: ['base', 'style', 'login'].reduce(function(o, v) { 
          o[v + '.css'] = 'less/' + v + '.less'
          return o
        }, {})
      }
    }
  })

  _.keys(pkg.devDependencies).forEach(function(name) {
    this.test(name) && grunt.loadNpmTasks(name)
  }, /^grunt-/)

  grunt.registerTask('test', ['jshint:all'])
  grunt.registerTask('build', ['less:build'])
  grunt.registerTask('default', ['test', 'less:build'])
};