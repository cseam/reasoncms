/*-----------------------------------------------------------------
  LOG
  GEM - Graphics Environment for Multimedia

  apply a 2p2z-filter on a sequence of pixBlocks
  
  Copyright (c) 1997-1999 Mark Danks. mark@danks.org
  Copyright (c) G�nther Geiger. geiger@epy.co.at
  Copyright (c) 2001-2002 IOhannes m zmoelnig. forum::f�r::uml�ute. IEM. zmoelnig@iem.kug.ac.at
  For information on usage and redistribution, and for a DISCLAIMER OF ALL
  WARRANTIES, see the file, "GEM.LICENSE.TERMS" in this distribution.
	
  -----------------------------------------------------------------*/

/*-----------------------------------------------------------------
  pix_tIIR

  IOhannes m zmoelnig
  mailto:zmoelnig@iem.kug.ac.at
	
  this code is published under the Gnu GeneralPublicLicense that should be distributed with gem & pd
	  
  -----------------------------------------------------------------*/

#ifndef INCLUDE_PIX_TIIR_H_
#define INCLUDE_PIX_TIIR_H_

#include "Base/GemPixObj.h"

/*-----------------------------------------------------------------
  -------------------------------------------------------------------
  CLASS
  pix_tIIR

  KEYWORDS
  pix
  
  DESCRIPTION
	
  -----------------------------------------------------------------*/
class GEM_EXTERN pix_tIIR : public GemPixObj
{
  CPPEXTERN_HEADER(pix_tIIR, GemPixObj)
		
    public:
	
  //////////
  // Constructor
  pix_tIIR(t_floatarg,t_floatarg);
	
 protected:
	
  //////////
  // Destructor
  virtual ~pix_tIIR();

  //////////
  // Do the processing
  virtual void 	processImage(imageStruct &image);

  //////////
  // set-flag
  bool set;      // set the buffers
  bool set_zero; // and set them to zero
	
  //////////
  // the filter factors (feed-forward, feed-back)
  t_float *m_ff, *m_fb;
  int ff_count, fb_count;

  t_inlet **m_inlet;

  //////////
  // the image-latches
  imageStruct m_buffer;
  // how many images are stored in m_buffer ?
  int         m_bufnum;

  // which buffer-image is the current one ?
  int m_counter;
	
  //////////
  // the methods
  static void setMessCallback(void *data, t_symbol*,int,t_atom*);
};

#endif	// for header file
