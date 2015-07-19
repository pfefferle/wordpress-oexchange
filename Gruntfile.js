module.exports = function(grunt) {
  // Project configuration.
  grunt.initConfig({
    wp_readme_to_markdown: {
      target: {
        files: {
          'README.md': 'readme.txt'
        },
      },
    },
    replace: {
      dist: {
        options: {
          patterns: [
            {
              match: /^/,
              replacement: '[![WordPress](https://img.shields.io/wordpress/v/oexchange.svg?style=flat-square)](https://wordpress.org/plugins/oexchange/) [![WordPress plugin](https://img.shields.io/wordpress/plugin/v/oexchange.svg?style=flat-square)](https://wordpress.org/plugins/oexchange/changelog/) [![WordPress](https://img.shields.io/wordpress/plugin/dt/oexchange.svg?style=flat-square)](https://wordpress.org/plugins/oexchange/) \n\n'
            }
          ]
        },
        files: [
          {
            src: ['README.md'],
            dest: './'
          }
        ]
      }
    }
  });

  grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
  grunt.loadNpmTasks('grunt-replace');

  // Default task(s).
  grunt.registerTask('default', ['wp_readme_to_markdown', 'replace']);
};
