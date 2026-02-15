import app from 'flarum/forum/app';
import addLikesCount from './addLikesCount';

app.initializers.add('commently-sort-by-likes', () => {
  addLikesCount();
});
