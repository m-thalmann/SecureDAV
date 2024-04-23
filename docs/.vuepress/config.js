import { viteBundler } from '@vuepress/bundler-vite';
import { defaultTheme } from '@vuepress/theme-default';
import { defineUserConfig } from 'vuepress';

export default defineUserConfig({
  bundler: viteBundler(),
  theme: defaultTheme({
    logo: '/assets/icon.png',
    repo: 'm-thalmann/SecureDAV',
    docsDir: 'docs',

    editLinkText: 'Edit this page on GitHub',

    sidebar: {
      '/': [
        {
          text: 'Getting Started',
          children: [
            '/introduction.md',
            {
              text: 'Installation',
              children: ['/installation/requirements.md', '/installation/docker.md', '/installation/source.md'],
            },
            '/configuration.md',
          ],
        },
      ],
    },
  }),

  lang: 'en-US',
  title: 'SecureDAV Docs',
  description: 'The SecureDAV documentation',
  base: '/SecureDAV/',

  head: [
    [
      'link',
      {
        rel: 'icon',
        type: 'image/x-icon',
        sizes: '128x128',
        href: `/SecureDAV/assets/favicon.ico`,
      },
    ],
    ['meta', { name: 'application-name', content: 'SecureDAV Docs' }],
    ['meta', { name: 'apple-mobile-web-app-title', content: 'SecureDAV Docs' }],
  ],
});
