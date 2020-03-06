import authSaga from './AuthSaga'
import commonSaga from './CommonSaga';
import homeSaga from './HomeSaga';

//Main Root Saga
const rootSaga = function* rootSaga() {
  yield* authSaga()
  yield* homeSaga()
  yield* commonSaga()
};
export default rootSaga;
