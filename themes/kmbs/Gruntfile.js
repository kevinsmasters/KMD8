module.exports = function(grunt) {
  const sass = require("node-sass");

  require("load-grunt-tasks")(grunt);

  grunt.initConfig({
    sass: {
      options: {
        implementation: sass,
        sourceMap: true
      },
      dist: {
        files: {
          "css/style.css": "scss/*.scss"
        }
      }
    },
    watch: {
      css: {
        files: "scss/**",
        tasks: ["sass"]
      }
    }
  });

  grunt.loadNpmTasks("grunt-sass");
  grunt.loadNpmTasks("grunt-contrib-watch");
  grunt.registerTask("default", ["watch", "sass"]);
};
